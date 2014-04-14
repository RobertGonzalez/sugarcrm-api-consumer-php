    <form method="post" action="<?php echo $this->formaction ?>?k=<?php echo time() ?>">
        <fieldset id="login-fieldset">
            <legend>Please login to continue</legend>
            <?php if ($this->error): ?>
            <p class="error">
                <?php echo $this->error ?>
            </p>
            <?php endif ?>
            <p>
                Username: <input type="text" name="username" id="username" value="<?php echo $this->username ?>" />
            </p>
            <p>
                Password: <input type="password" name="password" id="password" />
            </p>
            <p>
                <input type="submit" name="submit" value="Login" />
            </p>
        </fieldset>
    </form>