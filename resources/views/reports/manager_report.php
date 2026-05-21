<?php
/**
 * Template: Manager Daily Report
 * $manager - массив с информацией о менеджере
 * $invoices - массив оплаченных счетов менеджера
 * $isEmpty - флаг, есть ли счета
 */
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Отчёт об оплатах</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background-color: #f5f5f5;
            margin: 0;
            padding: 0;
            color: #333;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background-color: #ffffff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }
        .header {
            border-bottom: 3px solid #2196F3;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .header h1 {
            margin: 0;
            color: #2196F3;
            font-size: 24px;
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
            font-size: 13px;
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
            color: #2196F3;
        }
        .summary {
            background-color: #e8f5e9;
            border: 1px solid #4caf50;
            border-radius: 4px;
            padding: 20px;
            margin: 20px 0;
        }
        .summary h3 {
            margin: 0 0 15px 0;
            color: #2e7d32;
            font-size: 16px;
        }
        .summary-row {
            display: flex;
            justify-content: space-between;
            margin: 10px 0;
            font-size: 14px;
        }
        .summary-row .label {
            font-weight: 600;
            color: #333;
        }
        .summary-row .value {
            color: #2e7d32;
            font-weight: 700;
            font-size: 18px;
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
            color: #2196F3;
        }
        .contact-name {
            color: #666;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Ежедневный отчёт об оплатах</h1>
            <p>Сотрудник: <strong><?php echo htmlspecialchars($manager['NAME'] ?? 'Unknown'); ?></strong></p>
        </div>

        <div class="report-date">
            Отчёт за: <?php echo date('d.m.Y', strtotime('-1 day')); ?> | Сформирован: <?php echo date('d.m.Y H:i'); ?>
        </div>

        <?php if ($isEmpty): ?>
            <div class="no-data">
                <strong>За вчера оплат по вашим сделкам не было.</strong>
            </div>
            
            <div style="margin-top: 30px; padding: 20px; background-color: #f9f9f9; border-left: 4px solid #999;">
                <p>Привет! </p>
                <p><?php echo date('d.m.Y', strtotime('-1 day')); ?> г. море молчит – ни одного оплаченного счета.</p>
                <p style="margin-top: 20px;">С уважением,<br><strong>Ваш Робо Иванович</strong></p>
            </div>
        <?php else: ?>
            <div class="summary">
                <h3>Итого:</h3>
                <div class="summary-row">
                    <span class="label">Количество платежей:</span>
                    <span class="value"><?php echo count($invoices); ?></span>
                </div>
                <div class="summary-row">
                    <span class="label">Сумма всех платежей:</span>
                    <span class="value"><?php echo number_format(array_sum(array_column($invoices, 'opportunity')), 2, ',', ' '); ?> ₽</span>
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
                            $contractField = $_ENV['BITRIX24_CONTRACT_NUMBER_FIELD'] ?? 'UF_CRM_SMART_INVOICE_1758806730';
                            $contractNumber = $invoice[$contractField] ?? 'N/A';
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
                                <td><?php echo htmlspecialchars($deal['ASSIGNED_BY'] ?? 'N/A'); ?></td>
                                <td><?php echo $dateModify; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <div style="margin-top: 30px; padding: 20px; background-color: #f0f8f0; border-left: 4px solid #4caf50;">
                <p>Привет! </p>
                <p><?php echo date('d.m.Y', strtotime('-1 day')); ?> г. зафиксировали лёгкую волну поступлений на сумму: <strong><?php echo number_format(array_sum(array_column($invoices, 'opportunity')), 2, ',', ' '); ?> ₽</strong></p>
                <p>Подробнее в таблице выше.</p>
                <p style="margin-top: 20px;"><strong>Хорошее начало дня!</strong></p>
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
