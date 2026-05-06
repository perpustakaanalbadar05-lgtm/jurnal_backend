<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #e1e1e1; border-radius: 10px; }
        .header { text-align: center; margin-bottom: 30px; }
        .btn { display: inline-block; padding: 12px 25px; background-color: #005F02; color: #ffffff !important; text-decoration: none; border-radius: 5px; font-weight: bold; margin: 20px 0; }
        .footer { font-size: 12px; color: #888; text-align: center; margin-top: 30px; }
    </style>
</head>
<body>
    <div className="container">
        <div className="header">
            <h2>Reset Kata Sandi ABDImu</h2>
        </div>
        <p>Halo,</p>
        <p>Anda menerima email ini karena kami menerima permintaan reset kata sandi untuk akun Anda di sistem <strong>Platform Terintegrasi Penelitian dan Pengabdian Masyarakat IAIMU (ABDImu)</strong>.</p>
        <p>Silakan klik tombol di bawah ini untuk mengatur ulang kata sandi Anda:</p>
        
        <div style="text-align: center;">
            <a href="{{ $url }}" class="btn">Reset Kata Sandi</a>
        </div>
        
        <p>Link reset ini akan kadaluwarsa dalam 60 menit.</p>
        <p>Jika Anda tidak merasa melakukan permintaan ini, abaikan saja email ini.</p>
        
        <div className="footer">
            <p>&copy; {{ date('Y') }} ABDImu - Institut Agama Islam Miftahul Ulum (IAIMU). All rights reserved.</p>
        </div>
    </div>
</body>
</html>
