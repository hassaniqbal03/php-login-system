<?php
session_start();

// Check if block time is still active
$blocked = isset($_SESSION['otp_block_until']) && time() < $_SESSION['otp_block_until'];
$remaining_seconds = $blocked ? $_SESSION['otp_block_until'] - time() : 0;

// If block expired, allow button to go back
$can_return = !$blocked;

// SweetAlert2 script for the countdown
$sweet_alert_script = '';
if ($blocked) {
    $sweet_alert_script = "
    <script>
        let timerInterval;
        Swal.fire({
            title: 'You Have Been Temporarily Blocked!',
            // HTML for the SweetAlert modal, directly showing the countdown
            html: 'You entered incorrect OTP 3 times. Please wait <b></b> seconds.',
            icon: 'warning',
            timer: " . ($remaining_seconds * 1000) . ", // SweetAlert expects milliseconds
            timerProgressBar: true,
            allowOutsideClick: false, // Prevent closing by clicking outside
            allowEscapeKey: false,   // Prevent closing by pressing Escape key
            showConfirmButton: false, // No confirmation button needed as it's a timer
            didOpen: () => {
                const b = Swal.getHtmlContainer().querySelector('b');
                timerInterval = setInterval(() => {
                    const remaining = Math.round(Swal.getTimerLeft() / 1000);
                    b.textContent = remaining;
                }, 100);
            },
            willClose: () => {
                clearInterval(timerInterval);
                // When timer ends, reload the page to enable the button/clear block
                window.location.reload(); 
            }
        });
    </script>";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Too Many OTP Attempts</title>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            background: #ffeeee; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            height: 100vh; 
            margin: 0;
        }
        .box { 
            background: white; 
            padding: 30px; 
            border-radius: 12px; 
            box-shadow: 0 0 10px rgba(0,0,0,0.2); 
            text-align: center; 
            width: 350px;
            max-width: 90%;
        }
        h2 { 
            color: #d8000c; 
            margin-bottom: 15px;
        }
        p { 
            margin: 15px 0; 
            font-size: 1.1em;
            color: #555;
        }
        b {
            color: #d8000c; /* To make the countdown numbers stand out */
        }
        button {
            margin-top: 20px;
            padding: 10px 20px;
            background: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1em;
            transition: background-color 0.3s ease;
        }
        button:hover { 
            background: #0056b3; 
        }
        button:disabled { 
            background: #cccccc; 
            cursor: not-allowed; 
        }
    </style>
</head>
<body>
<?php if ($blocked): ?>
    <?= $sweet_alert_script ?>
<?php else: ?>
    <div class="box">
        <h2>You've been temporarily blocked!</h2>
        <p>You entered an incorrect OTP 3 times.</p>
        <p>You can try again now.</p>

        <form method="GET" action="<?php
            if (isset($_SESSION['pending_user']['email'])) {
                echo 'verify_otp.php';
            } elseif (isset($_SESSION['forgot']['email'])) {
                echo 'reset_verify.php';
            } else {
                echo 'user_login.php';
            }
        ?>">
            <input type="hidden" name="email" value="<?= htmlspecialchars($_SESSION['pending_user']['email'] ?? $_SESSION['forgot']['email'] ?? '') ?>">
            <?php if (isset($_SESSION['pending_user']['email']) || isset($_SESSION['forgot']['email'])): ?>
                <input type="hidden" name="resend" value="1"> 
            <?php endif; ?>
            <button type="submit">Go Back and Resend OTP</button>
        </form>
    </div>
<?php endif; ?>
</body>

    
</html>