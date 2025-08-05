<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

if (!function_exists('str_contains')) {
    function str_contains(string $haystack, string $needle): bool
    {
        return '' === $needle || false !== strpos($haystack, $needle);
    }
}

$receiptNumber = $_GET['receiptNumber'] ?? '';
?>
<!DOCTYPE html>
<html>
<head><meta charset="utf-8"><title>Receipt Info</title></head>
<body>
<form method="get">
    <input type="text" name="receiptNumber" value="<?= htmlspecialchars($receiptNumber) ?>" placeholder="CH29W2Z4XX" required>
    <button type="submit">Get Info</button>
</form>
<?php
if ($receiptNumber) {
    $url = "https://transactioninfo.ethiotelecom.et/receipt/$receiptNumber";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    $html = curl_exec($ch);

    if (curl_errno($ch)) {
        echo "<p style='color:red;'>cURL Error: " . curl_error($ch) . "</p>";
        curl_close($ch);
        exit;
    }
    curl_close($ch);

    if (empty($html)) {
        echo "<p style='color:red;'>No response received from server (blocked or invalid receipt).</p>";
        exit;
    }

    libxml_use_internal_errors(true);
    $dom = new DOMDocument();
    if (!@$dom->loadHTML($html)) {
        echo "<p style='color:red;'>Failed to parse HTML</p>";
        exit;
    }
    libxml_clear_errors();
    $xpath = new DOMXPath($dom);

    $payerName = '';
    $totalAmount = '';
    foreach ($xpath->query('//tr') as $row) {
        $cols = $row->getElementsByTagName('td');
        if ($cols->length >= 2) {
            $label = trim($cols->item(0)->nodeValue);
            $value = trim($cols->item(1)->nodeValue);
            if (str_contains($label, 'Payer Name') || str_contains($label, 'የከፋይ ስም')) $payerName = $value;
            if (str_contains(strtolower($label), 'amount') || str_contains($label, 'የተከፈለው ጠቅላላ መጠን')) $totalAmount = $value;
        }
    }

    echo "<h2>Receipt: " . htmlspecialchars($receiptNumber) . "</h2>";
    echo "<p><b>Payer Name:</b> " . htmlspecialchars($payerName ?: 'Not Found') . "</p>";
    echo "<p><b>Total Amount:</b> " . htmlspecialchars($totalAmount ?: 'Not Found') . "</p>";
}
?>
</body>
</html>
