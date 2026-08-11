<?php

namespace App\Services;

use Exception;
use App\Core\Logger;

class Bitrix24Service
{
    public function __construct()
    {
  
    }

    /**
     * Получить счета со статусом "Оплачен" за предыдущий день
     *
     * @return array
     */
    public function getPaidInvoicesForYesterday(): array
    {
        try {
            $yesterday = date('Y-m-d', strtotime('-1 day'));
            $yesterdayStart = $yesterday . ' 00:00:00';
            $yesterdayEnd = $yesterday . ' 23:59:59';
            
            Logger::message("Fetching paid invoices for: {$yesterday}");

            // Получаем все счета со статусом "Оплачен" через универсальный метод crm.item.list
            // entityTypeId = 31 — счета (устаревший crm.invoice.list больше не поддерживается)
            // Поля смарт-процесса используют camelCase: parentId2, contactIds, companyId, assignedById, movedTime
            $contractField = $_ENV['BITRIX24_CONTRACT_NUMBER_FIELD'] ?? 'ufCrm_SMART_INVOICE_1758806730';
            $stageId = $_ENV['BITRIX24_PAID_INVOICE_STATUS'] ?? 'DT31_11:P';
            $params = [
                'entityTypeId' => 31,
                'filter' => [
                    '>movedTime' => $yesterdayStart,
                    '<movedTime' => $yesterdayEnd,
                    "stageId" => $stageId,
                ],
                'select' => [
                    'id',
                    'title',
                    'opportunity',
                    'parentId2',     // ID сделки
                    'contactIds',    // ID контрагента (массив)
                    'companyId',     // ID компании
                    'assignedById',  // Ответственный
                    'movedTime',     // Дата платежа
                    $contractField,  // Номер договора (UF_CRM_SMART_INVOICE_*)
                ]
            ];
            $response = CRest::call('crm.item.list', $params);
            // crm.item.list возвращает данные в result.items[], в отличие от crm.invoice.list (result[])
            $invoices = $response['result']['items'] ?? [];
            Logger::message("Found " . count($invoices) . " paid invoices for yesterday");
            return $invoices;
        } catch (Exception $e) {
            Logger::message("Error fetching paid invoices: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Получить информацию о сделке
     *
     * @param int $dealId
     * @return array|null
     */
    public function getDealInfo(int $dealId): ?array
    {
        try {
            $response = CRest::call('crm.deal.get', ['id' => $dealId]);
            return $response['result'] ?? null;
        } catch (Exception $e) {
            Logger::message("Error fetching deal info for ID {$dealId}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Получить информацию о контакте
     *
     * @param int $contactId
     * @return array|null
     */
    public function getContactInfo(int $contactId): ?array
    {
        try {
            $response = CRest::call('crm.contact.get', ['id' => $contactId]);
            return $response['result'] ?? null;
        } catch (Exception $e) {
            Logger::message("Error fetching contact info for ID {$contactId}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Получить информацию о компании
     *
     * @param int $companyId
     * @return array|null
     */
    public function getCompanyInfo(int $companyId): ?array
    {
        try {
            $response = CRest::call('crm.company.get', ['id' => $companyId]);
            return $response['result'] ?? null;
        } catch (Exception $e) {
            Logger::message("Error fetching company info for ID {$companyId}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Получить пользователей из отдела продаж
     * 
     * Получает активных пользователей, которые имеют роль в отделе продаж.
     * В Bitrix24 Cloud используется поле 'UF_DEPARTMENT' для связи с отделом.
     *
     * @return array
     */
    public function getSalesManagersByDepartment(): array
    {
        try {
            $departmentId = $_ENV['BITRIX24_DEPARTMENT_ID'] ?? 1;
            
            // Получаем всех активных пользователей
            $params = [
                'FILTER' => [
                    'ACTIVE' => true,
                    'UF_DEPARTMENT' => $departmentId,
                ],
                'SELECT' => [
                    'ID',
                    'NAME',
                    'LAST_NAME',
                    'SECOND_NAME',
                    'EMAIL',
                    'PERSONAL_PHONE',
                    'WORK_DEPARTMENT',
                ]
            ];

            $response = CRest::call('user.get', $params);

            // user.get возвращает result -> result -> [пользователи]
            $managers = $response['result'] ?? [];

            Logger::message("Found " . count($managers) . " users in sales department {$departmentId}");
            return $managers;
        } catch (Exception $e) {
            Logger::message("Error fetching sales managers: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Получить информацию о пользователе
     *
     * @param int $userId
     * @return array|null
     */
    public function getUserInfo(int $userId): ?array
    {
        try {
            $response = CRest::call('user.get', ['ID' => $userId]);
            $users = $response['result'] ?? [];
            return $users[0] ?? null;
        } catch (Exception $e) {
            Logger::message("Error fetching user info for ID {$userId}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Отправить личное сообщение в Bitrix24
     *
     * @param int $userId ID получателя
     * @param string $message Текст сообщения
     * @return bool
     */
    public function sendMessage(int $userId, string $message): bool
    {
        try {
            $params = [
                'DIALOG_ID' => "USER_{$userId}",
                'MESSAGE' => $message,
            ];

            $response = CRest::call('im.message.add', $params);
            
            if (!isset($response['result']) || !$response['result']) {
                throw new Exception("Failed to send message to user {$userId}");
            }

            Logger::message("Message sent to user {$userId}");
            return true;
        } catch (Exception $e) {
            Logger::message("Error sending message to user {$userId}: " . $e->getMessage());
            return false;
        }
    }
}
