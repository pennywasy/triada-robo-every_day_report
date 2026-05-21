<?php

namespace App\Console\Commands;

use App\Core\Command;
use App\Services\ReportService;
use Exception;

class SendDailyReportsCommand extends Command
{
    protected string $name = 'send:daily-reports';
    protected string $description = 'Send daily reports of paid invoices to managers';

    public function handle(array $arguments): int
    {
        try {
            $this->info('Starting daily reports generation...');

            // Проверяем наличие параметра --test-email для тестовой отправки
            $testEmail = $this->getOption('test-email', $arguments);
            
            if ($testEmail) {
                $this->info('Test mode: sending all reports to: ' . $testEmail);
            }

            $reportService = new ReportService($testEmail);
            $results = $reportService->generateAndSendDailyReports();

            $this->printResults($results);

            if (!empty($results['errors'])) {
                return 1;
            }

            return 0;
        } catch (Exception $e) {
            $this->error('Error: ' . $e->getMessage());
            return 1;
        }
    }

    /**
     * Получить значение опции из аргументов
     */
    private function getOption(string $optionName, array $arguments): ?string
    {
        foreach ($arguments as $arg) {
            if (strpos($arg, '--' . $optionName . '=') === 0) {
                return substr($arg, strlen('--' . $optionName . '='));
            }
        }
        return null;
    }

    /**
     * Вывести результаты обработки
     */
    private function printResults(array $results): void
    {
        $this->info('');
        $this->info('========== REPORT GENERATION RESULTS ==========');
        $this->info('Invoices found: ' . $results['invoices_found']);
        $this->info('Managers notified: ' . $results['managers_notified']);
        $this->info('Additional recipients notified: ' . $results['additional_recipients_notified']);

        if (!empty($results['errors'])) {
            $this->error('');
            $this->error('ERRORS:');
            foreach ($results['errors'] as $error) {
                $this->error('  - ' . $error);
            }
        }

        $this->info('==============================================');
    }
}

