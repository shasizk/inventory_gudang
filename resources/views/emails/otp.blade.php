<!DOCTYPE html>
<html lang="id">
<body style="margin:0;padding:24px;background:#eff2f3;font-family:Arial,Helvetica,sans-serif;color:#1b2a3a;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
        <tr>
            <td align="center">
                <table role="presentation" width="480" cellpadding="0" cellspacing="0" style="max-width:480px;width:100%;background:#ffffff;border-radius:10px;overflow:hidden;border:1px solid #cdd5db;">
                    <tr><td style="background:#1b2a3a;padding:18px 28px;color:#ffffff;font-size:18px;font-weight:bold;">
                        {{ config('app.name') }}
                    </td></tr>
                    <tr><td style="height:4px;background:#f2b705;font-size:0;line-height:0;">&nbsp;</td></tr>
                    <tr><td style="padding:28px;">
                        <p style="margin:0 0 12px;font-size:16px;">Halo, <b>{{ $name }}</b>.</p>
                        <p style="margin:0 0 20px;font-size:15px;line-height:1.5;color:#3b5167;">
                            Gunakan kode di bawah ini untuk memverifikasi email akun Anda.
                        </p>
                        <p style="margin:0 0 20px;padding:16px;background:#eff2f3;border-radius:8px;text-align:center;font-size:34px;font-weight:bold;letter-spacing:10px;font-family:'Courier New',monospace;">
                            {{ $code }}
                        </p>
                        <p style="margin:0 0 8px;font-size:14px;color:#53677b;">Kode berlaku selama <b>{{ $minutes }} menit</b>.</p>
                        <p style="margin:0;font-size:14px;color:#53677b;">Jangan bagikan kode ini kepada siapa pun. Abaikan email ini bila Anda tidak merasa mendaftar.</p>
                    </td></tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>