<?php
// stripe_payment.php
// Stripe test payment integration for booking

require 'vendor/autoload.php';

// Set your Stripe secret key (test key)
\Stripe\Stripe::setApiKey('sk_test_XXXXXXXXXXXXXXXXXXXXXXXXsk_test_51SaWfrHXfXsWgJRrBqWi6c10Hh1eOf53QdIf7krAOIeRT6uCSaVjbCnaCSxxb8KMlftgAst8gDBNbK61EGZ1V1NB00rTChaF7r'); // Replace with your test key

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['stripeToken'])) {
    $token = $_POST['stripeToken'];
    $amount = $_POST['amount']; // Amount in cents
    $description = $_POST['description'];

    try {
        $charge = \Stripe\Charge::create([
            'amount' => $amount,
            'currency' => 'usd',
            'description' => $description,
            'source' => $token,
        ]);
        $success = true;
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Stripe Test Payment</title>
    <script src="https://js.stripe.com/v3/"></script>
    <style>
        body { font-family: Arial, sans-serif; background: #f8f8f8; }
        .container { max-width: 400px; margin: 40px auto; background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 0 10px #ccc; }
        .btn { background: #6772e5; color: #fff; padding: 10px 20px; border: none; border-radius: 5px; font-size: 1em; cursor: pointer; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Stripe Test Payment</h2>
        <?php if (isset($success) && $success): ?>
            <p style="color:green;">Payment successful!</p>
        <?php elseif (isset($error)): ?>
            <p style="color:red;">Error: <?php echo $error; ?></p>
        <?php endif; ?>
        <form id="payment-form" method="POST">
            <input type="hidden" name="amount" value="1000"> <!-- $10.00 -->
            <input type="hidden" name="description" value="Test Booking Payment">
            <div id="card-element"></div>
            <button class="btn" type="submit">Pay $10.00</button>
        </form>
    </div>
    <script>
        var stripe = Stripe('pk_test_XXXXXXXXXXXXXXXXXXXXXXXX'); // Replace with your test publishable key
        var elements = stripe.elements();
        var card = elements.create('card');
        card.mount('#card-element');

        var form = document.getElementById('payment-form');
        form.addEventListener('submit', function(event) {
            event.preventDefault();
            stripe.createToken(card).then(function(result) {
                if (result.error) {
                    alert(result.error.message);
                } else {
                    var input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'stripeToken';
                    input.value = result.token.id;
                    form.appendChild(input);
                    form.submit();
                }
            });
        });
    </script>
</body>
</html>
