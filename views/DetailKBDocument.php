    <p>
        <a href="<?php echo $this->formaction ?>">&laquo; Back to list</a>
    </p>
    <form method="post" action="<?php echo $this->formaction ?>" enctype="multipart/form-data">
        <fieldset id="contact-fieldset">
            <legend><?php echo $this->document->kbdocument_name; ?></legend>
            <?php if ($this->error): ?>
            <p class="error">
                <?php echo $this->error ?>
            </p>
            <?php endif ?>
            <p>
                Status: <?php echo $this->document->status_id ?><br />
                Revision: <?php echo $this->document->latest_revision ?>
            </p>
            <p>Current Attachments (<?php echo count($this->attachments) ?>):</p>
            <ul>
                <?php foreach ($this->attachments as $field => $attachment): ?>
                <li><?php echo $attachment['link'] ?></li>
                <?php endforeach ?>
            </ul>
            <!--
            <p>
                <input type="hidden" name="id" value="<?php echo $this->document->id ?>" />
                <input type="hidden" name="action" value="detail" />
                <input type="submit" name="submit" value="Save" />
            </p>
            -->
        </fieldset>
    </form>