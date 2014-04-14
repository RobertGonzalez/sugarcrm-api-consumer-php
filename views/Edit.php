    <form method="post" action="<?php echo $this->formaction ?>">
        <fieldset id="login-fieldset">
            <legend><?php if ($this->id): echo 'Edit ' . $this->moduleSingular; else: echo $this->getModuleString('LNK_CREATE'); endif; ?></legend>
            <?php if ($this->error): ?>
            <p class="error">
                <?php echo $this->error ?>
            </p>
            <?php endif ?>
            <input type="hidden" name="save" value="true" />
            <input type="hidden" name="id" value="<?php echo $this->id ?>" />
            <?php foreach ($this->metadata as $field => $def): ?>
            <p>
                <?php echo $this->getModuleString($def['vname']) ?><br /> <input type="text" name="<?php echo $field ?>" id="<?php echo $field ?>" value="<?php echo $this->bean->$field ?>" />
            </p>
            <?php endforeach; ?>
            <p>
                <input type="submit" name="submit" value="Save" />
            </p>
        </fieldset>
    </form>
