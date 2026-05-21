<?php

namespace App\Services;

use Exception;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use App\Core\Logger;

class EmailService
{
    private PHPMailer $mailer;

    public function __construct()
    {
        $this->mailer = new PHPMailer(true);
        $this->configureMailer();
    }

    /**
     * Настроить SMTP конфигурацию
     */
    private function configureMailer(): void
    {
        try {
            $this->mailer->isSMTP();
            $this->mailer->Host = $_ENV['SMTP_HOST'] ?? '';
            $this->mailer->SMTPAuth = true;
            $this->mailer->Username = $_ENV['SMTP_USERNAME'] ?? '';
            $this->mailer->Password = $_ENV['SMTP_PASSWORD'] ?? '';
            $secure = strtolower($_ENV['SMTP_SECURE'] ?? 'tls');
            $this->mailer->SMTPSecure = $secure === 'ssl'
                ? PHPMailer::ENCRYPTION_SMTPS
                : PHPMailer::ENCRYPTION_STARTTLS;
            $this->mailer->Port = (int)($_ENV['SMTP_PORT'] ?? ($secure === 'ssl' ? 465 : 587));
            $this->mailer->CharSet = 'UTF-8';

            Logger::message("SMTP configured: " . $this->mailer->Host);
        } catch (Exception $e) {
            Logger::message("Error configuring SMTP: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Отправить email отчёт
     *
     * @param string $toEmail Email получателя
     * @param string $toName Имя получателя
     * @param string $subject Тема письма
     * @param string $htmlContent HTML содержание письма
     * @return bool
     */
    public function sendReport(string $toEmail, string $toName, string $subject, string $htmlContent): bool
    {
        try {
            $this->mailer->clearAddresses();
            $this->mailer->clearReplyTos();
            
            $this->mailer->setFrom(
                $_ENV['EMAIL_FROM_ADDRESS'] ?? 'noreply@company.com',
                $_ENV['EMAIL_FROM_NAME'] ?? 'Reports'
            );
            
            $this->mailer->addAddress($toEmail, $toName);
            
            $this->mailer->isHTML(true);
            $this->mailer->Subject = $subject;
            $this->mailer->Body = $htmlContent;
            
            $this->mailer->send();
            
            Logger::message("Email sent to {$toEmail} (Subject: {$subject})");
            return true;
        } catch (Exception $e) {
            Logger::message("Error sending email to {$toEmail}: " . $e->getMessage());
            return false;
        }
    }

}
