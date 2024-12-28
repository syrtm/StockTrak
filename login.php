<?php
session_start();
require_once 'config/facebook_config.php';

if(isset($_SESSION['user_id'])) {
    header("Location: /stok-takip/index.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    require_once 'includes/db.php';
    
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    $sql = "SELECT * FROM users WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            header("Location: /stok-takip/index.php");
            exit();
        }
    }
    
    $error = "Kullanıcı adı veya şifre hatalı!";
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Giriş - Stok Takip Sistemi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #4361ee 0%, #3f37c9 100%);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-container {
            background: white;
            padding: 2rem;
            border-radius: 1rem;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 400px;
        }
        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        .login-header i {
            font-size: 3rem;
            color: #4361ee;
            margin-bottom: 1rem;
        }
        .form-control {
            border-radius: 0.5rem;
            padding: 0.75rem 1rem;
        }
        .btn-login {
            background: #4361ee;
            border: none;
            padding: 0.75rem;
            border-radius: 0.5rem;
            width: 100%;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .btn-login:hover {
            background: #3f37c9;
            color: white;
        }
        .divider {
            text-align: center;
            margin: 20px 0;
            position: relative;
        }
        .divider:before {
            content: "";
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            border-top: 1px solid #ddd;
            z-index: 1;
        }
        .divider span {
            background-color: #fff;
            padding: 0 15px;
            color: #6c757d;
            position: relative;
            z-index: 2;
        }
        .btn-facebook {
            background-color: #1877f2;
            color: white;
            width: 100%;
            margin-top: 10px;
            border: none;
            padding: 0.75rem;
            border-radius: 0.5rem;
            font-weight: 600;
        }
        .btn-facebook:hover {
            background-color: #166fe5;
            color: white;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <i class="fas fa-boxes"></i>
            <h2>Stok Takip Sistemi</h2>
            <p class="text-muted">Lütfen giriş yapın</p>
        </div>
        <?php if(isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        <form method="POST" action="">
            <div class="mb-3">
                <label for="username" class="form-label">Kullanıcı Adı</label>
                <input type="text" class="form-control" id="username" name="username" required>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Şifre</label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>
            <button type="submit" class="btn btn-login text-white">Giriş Yap</button>
        </form>

        <div class="divider">
            <span>veya</span>
        </div>

        <button onclick="loginWithFacebook()" class="btn btn-facebook">
            <i class="fab fa-facebook-f me-2"></i> Facebook ile Giriş Yap
        </button>
    </div>

    <script>
        window.fbAsyncInit = function() {
            FB.init({
                appId: '<?php echo FB_APP_ID; ?>',
                cookie: true,
                xfbml: true,
                version: 'v18.0'
            });
        };

        // Facebook SDK yükleme
        (function(d, s, id) {
            var js, fjs = d.getElementsByTagName(s)[0];
            if (d.getElementById(id)) return;
            js = d.createElement(s); js.id = id;
            js.src = "https://connect.facebook.net/tr_TR/sdk.js";
            fjs.parentNode.insertBefore(js, fjs);
        }(document, 'script', 'facebook-jssdk'));

        function loginWithFacebook() {
            FB.login(function(response) {
                if (response.authResponse) {
                    // Kullanıcı bilgilerini al
                    FB.api('/me', {fields: 'id,name,email'}, function(response) {
                        console.log('API Response:', response); // Debug için log
                        // Sunucuya gönder
                        fetch('pages/facebook_callback.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify(response)
                        })
                        .then(response => response.json())
                        .then(data => {
                            if(data.success) {
                                window.location.href = '/stok-takip/index.php';
                            } else {
                                alert('Giriş yapılırken bir hata oluştu: ' + (data.message || ''));
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            alert('Bir hata oluştu: ' + error);
                        });
                    });
                } else {
                    console.log('Login Response:', response); // Debug için log
                    alert('Facebook ile giriş yapılamadı!');
                }
            }, {scope: 'public_profile,email', return_scopes: true});
        }
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
