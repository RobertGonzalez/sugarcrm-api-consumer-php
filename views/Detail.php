        <fieldset id="login-fieldset">
            <legend><?php echo 'View ' . $this->moduleSingular ?></legend>
            <?php if ($this->error): ?>
            <p class="error">
                <?php echo $this->error ?>
            </p>
            <?php endif ?>
            <p><a href="<?php echo $this->formaction ?>?action=edit&id=<?php echo $this->id ?>" />Edit</a></p>
            <?php foreach ($this->metadata as $field => $def): ?>
            <p>
                <strong><?php echo $this->getModuleString($def['vname']) ?></strong> <?php echo $this->bean->$field ?>
            </p>
            <?php endforeach; ?>
        </fieldset>
