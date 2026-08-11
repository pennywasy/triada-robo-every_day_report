<?php
/**
 * Template: Extended Daily Report (for additional recipients)
 * $recipient - массив с информацией о получателе
 * $invoices - массив всех оплаченных счетов
 * $isEmpty - флаг, есть ли счета
 */
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Общий отчёт об оплатах</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background-color: #f5f5f5;
            margin: 0;
            padding: 0;
            color: #333;
        }
        .container {
            max-width: 1000px;
            margin: 0 auto;
            background-color: #ffffff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }
        .header {
            border-bottom: 3px solid #ff6f00;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .header h1 {
            margin: 0;
            color: #ff6f00;
            font-size: 28px;
        }
        .header p {
            margin: 10px 0 0 0;
            color: #666;
            font-size: 14px;
        }
        .report-date {
            background-color: #f0f0f0;
            padding: 10px 15px;
            border-radius: 4px;
            margin-bottom: 20px;
            font-size: 13px;
            color: #666;
        }
        .no-data {
            background-color: #fff3cd;
            border: 1px solid #ffc107;
            border-radius: 4px;
            padding: 20px;
            text-align: center;
            color: #856404;
            margin: 20px 0;
        }
        .table-wrapper {
            overflow-x: auto;
            margin: 20px 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        thead {
            background-color: #f5f5f5;
            border-bottom: 2px solid #ddd;
        }
        th {
            text-align: left;
            padding: 12px;
            font-weight: 600;
            color: #333;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        td {
            padding: 12px;
            border-bottom: 1px solid #eee;
            font-size: 13px;
        }
        tr:hover {
            background-color: #fafafa;
        }
        .amount {
            font-weight: 600;
            color: #ff6f00;
        }
        .summary {
            background-color: #fff3e0;
            border: 2px solid #ff6f00;
            border-radius: 4px;
            padding: 20px;
            margin: 20px 0;
        }
        .summary h3 {
            margin: 0 0 15px 0;
            color: #e65100;
            font-size: 16px;
        }
        .summary-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        .summary-item {
            text-align: center;
            padding: 15px;
            background-color: #ffffff;
            border-radius: 4px;
            border-left: 4px solid #ff6f00;
        }
        .summary-item .label {
            font-size: 12px;
            color: #666;
            text-transform: uppercase;
            margin-bottom: 5px;
        }
        .summary-item .value {
            font-size: 28px;
            font-weight: 700;
            color: #e65100;
        }
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            text-align: center;
            color: #999;
            font-size: 12px;
        }
        .deal-name {
            font-weight: 600;
            color: #ff6f00;
        }
        .manager-badge {
            display: inline-block;
            background-color: #e0e0e0;
            padding: 4px 8px;
            border-radius: 3px;
            font-size: 11px;
            margin-top: 4px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Общий отчёт об оплатах</h1>
            <p>Для: <strong><?php echo htmlspecialchars($recipient['NAME'] ?? 'Unknown'); ?></strong></p>
        </div>

        <div class="report-date">
            Отчёт за: <?php echo date('d.m.Y', strtotime('-1 day')); ?> | Сформирован: <?php echo date('d.m.Y H:i'); ?>
        </div>

        <?php if ($isEmpty): ?>
            <div class="no-data">
                 <strong>За вчера оплат не было.</strong>
            </div>
            
            <div style="margin-top: 30px; padding: 20px; background-color: #f9f9f9; border-left: 4px solid #999;">
                <p>Привет! <?php echo date('d.m.Y', strtotime('-1 day')); ?> г. зафиксировали штиль: оплат нет!</p>
                <p style="margin-top: 20px;">С уважением,<br><strong>Ваш Робо Иванович</strong></p>
            </div>
        <?php else: ?>
            <div class="summary">
                <h3> ИТОГО:</h3>
                <div class="summary-grid">
                    <div class="summary-item">
                        <div class="label">Количество платежей</div>
                        <div class="value"><?php echo count($invoices); ?></div>
                    </div>
                    <div class="summary-item">
                        <div class="label">Сумма всех платежей</div>
                        <div class="value"><?php echo number_format(array_sum(array_column($invoices, 'opportunity')), 0, ',', ' '); ?></div>
                    </div>
                </div>
                <div style="text-align: center; margin-top: 10px; color: #ff6f00; font-weight: 600;">
                    ₽ <?php echo number_format(array_sum(array_column($invoices, 'opportunity')), 2, ',', ' '); ?>
                </div>
            </div>

            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>№ п/п</th>
                            <th>Сделка</th>
                            <th>Контрагент</th>
                            <th>Счет №</th>
                            <th>Договор №</th>
                            <th>Сумма</th>
                            <th>Ответственный</th>
                            <th>Дата платежа</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $index = 0; foreach ($invoices as $invoice): $index++; ?>
                            <?php
                            $deal = $invoice['deal'] ?? [];
                            $company = $invoice['company'] ?? [];
                            $contact = $invoice['contact'] ?? [];
                            $contractField = $_ENV['BITRIX24_CONTRACT_NUMBER_FIELD'] ?? 'UF_CRM_SMART_INVOICE_1758806730';
                            $contractNumber = $invoice[$contractField] ?? 'N/A';
                            $manager = $deal['ASSIGNED_BY_NAME'] ?? 'Unknown';
                            $dateModify = date('d.m.Y', strtotime($invoice['movedTime'] ?? 'now'));
                            ?>
                            <tr>
                                <td><?php echo $index; ?></td>
                                <td>
                                    <div class="deal-name"><?php echo htmlspecialchars($deal['TITLE'] ?? 'Unknown Deal'); ?></div>
                                </td>
                                <td><?php echo htmlspecialchars($company['TITLE'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($invoice['id'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($contractNumber); ?></td>
                                <td class="amount"><?php echo number_format($invoice['opportunity'] ?? 0, 2, ',', ' '); ?> ₽</td>
                                <td><?php echo htmlspecialchars($deal['ASSIGNED_BY_NAME'] ?? 'N/A'); ?></td>
                                <td><?php echo $dateModify; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <div style="margin-top: 30px; padding: 20px; background-color: #f0f8f0; border-left: 4px solid #4caf50;">
                <p>Привет! <?php echo date('d.m.Y', strtotime('-1 day')); ?> г. зафиксировали лёгкую волна поступлений на сумму: <strong><?php echo number_format(array_sum(array_column($invoices, 'opportunity')), 2, ',', ' '); ?> ₽</strong></p>
                <p>Таблица выше содержит все детали.</p>
                <p style="margin-top: 20px;">С уважением,<br><strong>Ваш Робо Иванович</strong></p>
            </div>
        <?php endif; ?>

        <div class="footer">
            <p>Это автоматизированное письмо. Не отвечайте на него. Вопросы: <a href="mailto:support@company.com">support@company.com</a></p>
            <p>&copy; <?php echo date('Y'); ?> Triada. Все права защищены.</p>
        </div>
    </div>
</body>
</html>
