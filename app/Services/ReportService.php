<?php

namespace App\Services;

use Exception;

class ReportService
{
    private Bitrix24Service $bitrix24;
    private EmailService $emailService;
    private \App\Core\Logger $logger;
    private \App\Core\View $view;
    private ?string $testEmail = null; // Для тестовой отправки

    public function __construct(?string $testEmail = null)
    {
        $this->bitrix24 = new Bitrix24Service();
        $this->emailService = new EmailService();
        $this->logger = new \App\Core\Logger();
        $this->view = new \App\Core\View();
        $this->testEmail = $testEmail; // Если передан тестовый email, отправляем только туда
    }

    /**
     * Выполнить ежедневную отправку отчётов
     * 
     * @return array Результаты обработки
     */
    public function generateAndSendDailyReports(): array
    {
        $this->logger->info("========== Starting daily reports generation ==========");
        
        $results = [
            'invoices_found' => 0,
            'managers_notified' => 0,
            'additional_recipients_notified' => 0,
            'errors' => []
        ];

        try {
            // 1. Получить оплаченные счета за вчера
            $invoices = $this->bitrix24->getPaidInvoicesForYesterday();
            $results['invoices_found'] = count($invoices);

            // 2. Получить менеджеров из отдела продаж
            $managers = $this->bitrix24->getSalesManagersByDepartment();
            $this->logger->info("Total managers: " . count($managers));

            // 3. Подготовить данные счетов с информацией о сделках и контрагентах
            $enrichedInvoices = $this->enrichInvoicesData($invoices);

            // 4. Отправить индивидуальные отчёты менеджерам
            foreach ($managers as $manager) {
                try {
                    $managerId = $manager['ID'];
                    $managerEmail = $manager['EMAIL'] ?? '';

                    if (empty($managerEmail)) {
                        $this->logger->warning("Manager {$managerId} has no email");
                        continue;
                    }

                    $managerInvoices = $this->filterInvoicesByManager($enrichedInvoices, $managerId);
                    
                    if ($this->sendManagerReport($manager, $managerInvoices)) {
                        $results['managers_notified']++;
                    }
                } catch (Exception $e) {
                    $this->logger->error("Error processing manager: " . $e->getMessage());
                    $results['errors'][] = "Manager {$manager['ID']}: " . $e->getMessage();
                }
            }

            // 5. Отправить расширенный отчёт дополнительным сотрудникам
            $additionalRecipients = $this->getAdditionalRecipients();
            foreach ($additionalRecipients as $recipient) {
                try {
                    $recipientId = $recipient['ID'];
                    $recipientEmail = $recipient['EMAIL'] ?? '';

                    if (empty($recipientEmail)) {
                        $this->logger->warning("Additional recipient {$recipientId} has no email");
                        continue;
                    }

                    if ($this->sendExtendedReport($recipient, $enrichedInvoices)) {
                        $results['additional_recipients_notified']++;
                    }
                } catch (Exception $e) {
                    $this->logger->logError("Error processing additional recipient: " . $e->getMessage());
                    $results['errors'][] = "Additional recipient {$recipient['ID']}: " . $e->getMessage();
                }
            }

            $this->logger->info("========== Daily reports generation completed ==========");
            return $results;

        } catch (Exception $e) {
            $this->logger->logError("Critical error in daily reports: " . $e->getMessage());
            $results['errors'][] = "Critical: " . $e->getMessage();
            return $results;
        }
    }

    /**
     * Обогатить данные счетов информацией о сделках и контрагентах
     *
     * @param array $invoices
     * @return array
     */
    private function enrichInvoicesData(array $invoices): array
    {
        $enriched = [];

        foreach ($invoices as $invoice) {
            // parentId2 — ID сделки (новое поле смарт-счетов entityTypeId=31)
            $dealId = $invoice['parentId2'] ?? null;
            // contactIds — массив ID контактов, companyId — ID компании
            $contactIds = $invoice['contactIds'] ?? [];
            $companyId = $invoice['companyId'] ?? null;

            if ($dealId) {
                $invoice['deal'] = $this->bitrix24->getDealInfo((int)$dealId);

                // Разрешаем ФИО ответственного через user.get (ASSIGNED_BY отсутствует в crm.deal.get)
                if (!empty($invoice['deal']['ASSIGNED_BY_ID'])) {
                    $responsibleUser = $this->bitrix24->getUserInfo((int)$invoice['deal']['ASSIGNED_BY_ID']);
                    $invoice['deal']['ASSIGNED_BY_NAME'] = trim(
                        ($responsibleUser['LAST_NAME'] ?? '') . ' ' .
                        ($responsibleUser['NAME'] ?? '') . ' ' .
                        ($responsibleUser['SECOND_NAME'] ?? '')
                    ) ?: 'N/A';
                } else {
                    $invoice['deal']['ASSIGNED_BY_NAME'] = 'N/A';
                }
            }

            // Компания (прямое поле)
            if ($companyId) {
                $invoice['company'] = $this->bitrix24->getCompanyInfo((int)$companyId);
            }

            // Контакты (первый из массива)
            if (!empty($contactIds) && is_array($contactIds)) {
                $contact = $this->bitrix24->getContactInfo((int)$contactIds[0]);
                if ($contact && !$companyId && !empty($contact['COMPANY_ID'])) {
                    $invoice['company'] = $this->bitrix24->getCompanyInfo((int)$contact['COMPANY_ID']);
                }
                $invoice['contact'] = $contact;
            }

            $enriched[] = $invoice;
        }

        return $enriched;
    }

    /**
     * Отфильтровать счета по менеджеру
     *
     * @param array $invoices
     * @param int $managerId
     * @return array
     */
    private function filterInvoicesByManager(array $invoices, int $managerId): array
    {
        return array_filter($invoices, function ($invoice) use ($managerId) {
            // Ответственный определяется только из привязанной сделки
            $deal = $invoice['deal'] ?? null;
            if ($deal) {
                $dealResponsibleId = $deal['ASSIGNED_BY_ID'] ?? null;
                return (int)$dealResponsibleId === $managerId;
            }

            return false;
        });
    }

    /**
     * Отправить индивидуальный отчёт менеджеру
     *
     * @param array $manager
     * @param array $invoices
     * @return bool
     */
    private function sendManagerReport(array $manager, array $invoices): bool
    {
        try {
            $managerId = $manager['ID'];
            $managerName = $manager['NAME'] ?? 'Manager';
            $managerEmail = $this->testEmail ?? $manager['EMAIL'];

            // Подготовить HTML содержание
            $htmlContent = $this->view->render('reports/manager_report', [
                'manager' => $manager,
                'invoices' => $invoices,
                'isEmpty' => empty($invoices)
            ]);

            // Тема письма
            $subject = empty($invoices) 
                ? 'Отчёт об оплатах: нет оплат'
                : 'Отчёт об оплатах: ' . count($invoices) . ' платеж(а)';

            // Отправить email
            $emailSent = $this->emailService->sendReport(
                $managerEmail,
                $managerName,
                $subject,
                $htmlContent
            );

            // Отправить сообщение в Bitrix24 (только если не тестовый режим)
            $messageSent = true;
            if (!$this->testEmail) {
                $message = $this->createBitrix24Message($manager, $invoices);
                $messageSent = $this->bitrix24->sendMessage($managerId, $message);
            }

            if ($emailSent && $messageSent) {
                $this->logger->info("Report sent to manager {$managerId}: email={$emailSent}, message={$messageSent}");
                return true;
            }

            return false;
        } catch (Exception $e) {
            $this->logger->logError("Error sending manager report: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Отправить расширенный отчёт дополнительным сотрудникам
     *
     * @param array $recipient
     * @param array $invoices
     * @return bool
     */
    private function sendExtendedReport(array $recipient, array $invoices): bool
    {
        try {
            $recipientId = $recipient['ID'];
            $recipientName = $recipient['NAME'] ?? 'Recipient';
            $recipientEmail = $this->testEmail ?? $recipient['EMAIL'];

            // Подготовить HTML содержание
            $htmlContent = $this->view->render('reports/extended_report', [
                'recipient' => $recipient,
                'invoices' => $invoices,
                'isEmpty' => empty($invoices)
            ]);

            // Тема письма
            $subject = empty($invoices)
                ? 'Общий отчёт об оплатах: нет оплат'
                : 'Общий отчёт об оплатах: ' . count($invoices) . ' платеж(а)';

            // Отправить email
            $emailSent = $this->emailService->sendReport(
                $recipientEmail,
                $recipientName,
                $subject,
                $htmlContent
            );

            // Отправить сообщение в Bitrix24 (только если не тестовый режим)
            $messageSent = true;
            if (!$this->testEmail) {
                $message = $this->createBitrix24ExtendedMessage($recipient, $invoices);
                $messageSent = $this->bitrix24->sendMessage($recipientId, $message);
            }

            if ($emailSent && $messageSent) {
                $this->logger->info("Extended report sent to {$recipientId}: email={$emailSent}, message={$messageSent}");
                return true;
            }

            return false;
        } catch (Exception $e) {
            $this->logger->logError("Error sending extended report: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Создать сообщение для Bitrix24 (для менеджера)
     *
     * @param array $manager
     * @param array $invoices
     * @return string
     */
    private function createBitrix24Message(array $manager, array $invoices): string
    {
        if (empty($invoices)) {
            return "За вчера оплат по вашим сделкам не было.";
        }

        $count = count($invoices);
        $total = array_sum(array_column($invoices, 'opportunity'));

        return "Новые оплаты!\n\n" .
               "За вчера получено {$count} платеж(а) на сумму " . number_format($total, 2, ',', ' ') . " ₽\n\n" .
               "Полный отчёт отправлен на ваш email.";
    }

    /**
     * Создать сообщение для Bitrix24 (для дополнительных сотрудников)
     *
     * @param array $recipient
     * @param array $invoices
     * @return string
     */
    private function createBitrix24ExtendedMessage(array $recipient, array $invoices): string
    {
        if (empty($invoices)) {
            return "За вчера оплат не было.";
        }

        $count = count($invoices);
        $total = array_sum(array_column($invoices, 'opportunity'));

        return "Ежедневный отчёт об оплатах\n\n" .
               "За вчера получено {$count} платеж(а) на сумму " . number_format($total, 2, ',', ' ') . " ₽\n\n" .
               "Полный отчёт отправлен на ваш email.";
    }

    /**
     * Получить список дополнительных сотрудников для расширенного отчёта
     *
     * @return array
     */
    private function getAdditionalRecipients(): array
    {
        try {
            $recipientIds = $_ENV['ADDITIONAL_REPORT_RECIPIENTS'] ?? '';
            
            if (empty($recipientIds)) {
                $this->logger->info("No additional recipients configured");
                return [];
            }

            $ids = array_filter(array_map('trim', explode(',', $recipientIds)));
            $recipients = [];

            foreach ($ids as $id) {
                $user = $this->bitrix24->getUserInfo((int)$id);
                if ($user) {
                    $recipients[] = $user;
                }
            }

            $this->logger->info("Found " . count($recipients) . " additional recipients");
            return $recipients;
        } catch (Exception $e) {
            $this->logger->logError("Error getting additional recipients: " . $e->getMessage());
            return [];
        }
    }
}
