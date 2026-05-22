<?php
/**
 * Mailversand via SMTP (ohne PHPMailer-Dependency)
 *
 * Eigenständige minimale SMTP-Implementierung — reicht für transaktionale
 * Login-Mails über webgo SMTP (s305.goserver.host:587, STARTTLS).
 */
declare(strict_types=1);

require_once __DIR__ . '/../config.php';

function send_mail(string $to, string $subject, string $bodyText, string $bodyHtml = ''): bool {
    $boundary = 'b_' . bin2hex(random_bytes(8));
    $headers = [];
    $headers[] = 'From: ' . MAIL_FROM_NAME . ' <' . MAIL_FROM . '>';
    $headers[] = 'To: <' . $to . '>';
    $headers[] = 'Subject: ' . encode_header($subject);
    $headers[] = 'MIME-Version: 1.0';
    $headers[] = 'Date: ' . date(DATE_RFC2822);
    $headers[] = 'Message-ID: <' . bin2hex(random_bytes(12)) . '@' . parse_url(APP_URL, PHP_URL_HOST) . '>';

    if ($bodyHtml !== '') {
        $headers[] = "Content-Type: multipart/alternative; boundary=\"$boundary\"";
        $body  = "--$boundary\r\n";
        $body .= "Content-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: 8bit\r\n\r\n";
        $body .= $bodyText . "\r\n\r\n";
        $body .= "--$boundary\r\n";
        $body .= "Content-Type: text/html; charset=UTF-8\r\nContent-Transfer-Encoding: 8bit\r\n\r\n";
        $body .= $bodyHtml . "\r\n\r\n";
        $body .= "--$boundary--\r\n";
    } else {
        $headers[] = 'Content-Type: text/plain; charset=UTF-8';
        $headers[] = 'Content-Transfer-Encoding: 8bit';
        $body = $bodyText;
    }

    return smtp_send($to, implode("\r\n", $headers) . "\r\n\r\n" . $body);
}

function encode_header(string $text): string {
    return '=?UTF-8?B?' . base64_encode($text) . '?=';
}

function smtp_send(string $to, string $raw): bool {
    $errno = 0; $errstr = '';
    $fp = @stream_socket_client(
        'tcp://' . SMTP_HOST . ':' . SMTP_PORT,
        $errno, $errstr, 15
    );
    if (!$fp) {
        error_log("SMTP connect failed: $errstr ($errno)");
        return false;
    }
    stream_set_timeout($fp, 15);

    $expect = function(int $code) use ($fp): bool {
        $line = '';
        do {
            $line = fgets($fp, 1024);
            if ($line === false) return false;
        } while (isset($line[3]) && $line[3] === '-');
        $ok = (int)substr($line, 0, 3) === $code;
        if (!$ok) error_log("SMTP unexpected: $line");
        return $ok;
    };
    $cmd = function(string $c) use ($fp): void {
        fwrite($fp, $c . "\r\n");
    };

    if (!$expect(220)) { fclose($fp); return false; }
    $cmd('EHLO ' . (parse_url(APP_URL, PHP_URL_HOST) ?: 'localhost'));
    if (!$expect(250)) { fclose($fp); return false; }

    // STARTTLS
    $cmd('STARTTLS');
    if (!$expect(220)) { fclose($fp); return false; }
    if (!stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
        error_log('SMTP STARTTLS failed');
        fclose($fp); return false;
    }
    $cmd('EHLO ' . (parse_url(APP_URL, PHP_URL_HOST) ?: 'localhost'));
    if (!$expect(250)) { fclose($fp); return false; }

    // AUTH LOGIN
    $cmd('AUTH LOGIN');
    if (!$expect(334)) { fclose($fp); return false; }
    $cmd(base64_encode(SMTP_USER));
    if (!$expect(334)) { fclose($fp); return false; }
    $cmd(base64_encode(SMTP_PASS));
    if (!$expect(235)) { fclose($fp); return false; }

    // MAIL FROM / RCPT TO / DATA
    $cmd('MAIL FROM:<' . MAIL_FROM . '>');
    if (!$expect(250)) { fclose($fp); return false; }
    $cmd('RCPT TO:<' . $to . '>');
    if (!$expect(250)) { fclose($fp); return false; }
    $cmd('DATA');
    if (!$expect(354)) { fclose($fp); return false; }

    // Body senden, dann CRLF.CRLF
    fwrite($fp, $raw . "\r\n.\r\n");
    if (!$expect(250)) { fclose($fp); return false; }

    $cmd('QUIT');
    fclose($fp);
    return true;
}

function send_magic_link_mail(string $to, string $name, string $link): bool {
    $subject = APP_NAME . ' — Login-Link';
    $minutes = TOKEN_MINUTES;
    $text = <<<TXT
Hallo $name,

dein Login-Link für den Bauzeitenplan Archivita:

$link

Der Link ist $minutes Minuten gültig und kann nur einmal verwendet werden.

Falls du diesen Login nicht angefordert hast, kannst du diese Mail ignorieren.

– Bauzeitenplan Archivita
TXT;

    $html = <<<HTML
<!DOCTYPE html><html><body style="font-family:Inter,Helvetica,sans-serif;background:#f5f6fa;padding:32px;color:#1e293b">
  <div style="max-width:520px;margin:0 auto;background:#fff;border-radius:14px;padding:28px 32px;box-shadow:0 1px 3px rgba(0,0,0,.06)">
    <h1 style="margin:0 0 12px;font-size:20px;letter-spacing:-.02em">Bauzeitenplan Archivita</h1>
    <p style="color:#475569;margin:0 0 24px">Hallo $name, dein Login-Link:</p>
    <p style="margin:0 0 24px">
      <a href="$link" style="display:inline-block;background:#2563eb;color:#fff;padding:12px 22px;border-radius:10px;text-decoration:none;font-weight:600">
        Jetzt einloggen
      </a>
    </p>
    <p style="font-size:12px;color:#64748b;margin:0 0 6px">
      Falls der Button nicht funktioniert, kopiere diesen Link in den Browser:<br>
      <span style="word-break:break-all;color:#2563eb">$link</span>
    </p>
    <p style="font-size:12px;color:#94a3b8;margin-top:24px">
      Der Link ist $minutes Minuten gültig und kann nur einmal verwendet werden.
    </p>
  </div>
</body></html>
HTML;

    return send_mail($to, $subject, $text, $html);
}
