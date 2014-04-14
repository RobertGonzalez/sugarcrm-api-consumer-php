<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <title>Robert's Sekret Sugar Storehouse (S<sup>3</sup>)<?php if ($this->module): ?> - <?php echo $this->module ?><?php endif; ?></title>
    <style>
    	html {
    		margin: 10px 20px;
    	}

        body {
            font-family: Verdana;
            font-size: 11pt;
            line-height: 15pt;
            width: 1024px;
            margin: 0 auto;
        }

        input[type="text"] {
            width: 200px;
        }

        table {
            border-collapse: collapse;
        }

        table, td, th {
            border: solid 1px #333;
        }

        th, td {
            padding: 3px 6px;
        }

        td {
            vertical-align: top;
        }

        th {
            background: #ccc;
        }

        a.small {
            display: inline-block;
            margin-top: 20px;
        }

        .small {
            font-size: smaller;
        }

        .row {
            border: solid 1px #ccc;
            border-bottom: none;
            padding: 10px;
        }
        .row.first {
            border-top-left-radius: 10px;
            border-top-right-radius: 10px;
        }
        .row.on {
            background-color: #efefef;
        }
        .row.last {
            text-align: center;
            border-bottom-left-radius: 10px 10px;
            border-bottom-right-radius: 10px 10px;
            border-bottom: solid 1px #ccc;
        }

        ul#listing {
            list-style: none;
            padding-left: 0;
        }

        .error {
            color: #800;
            border: solid 1px #800;
            padding: 3px 6px;
            background: #ffe9e8;
        }
    </style>
</head>
<body>
    <h1>Robert's Sekret Sugar Storehouse (S<sup>3</sup>)<?php if ($this->module): ?> - <?php echo $this->module ?><?php endif; ?></h1>
    <?php if (!empty($_SESSION['authtoken'])): ?>
    <form method="post" action="<?php echo $this->formaction ?>">
    <p>
        Select a module: <select name="module" id="module">
            <?php foreach ($this->modules as $module): ?>
            <option value="<?php echo $module ?>"<?php echo $module == $this->module ? ' selected="selected"' : ''; ?>><?php echo $module ?></option>
            <?php endforeach; ?>
        </select> &nbsp;&nbsp;
        Select a platform: <select name="platform" id="platform">
            <?php foreach ($this->platforms as $platform): ?>
            <option value="<?php echo $platform ?>"<?php echo $platform == $this->platform ? ' selected="selected"' : ''; ?>><?php echo $platform ?></option>
            <?php endforeach; ?>
        </select> &nbsp;&nbsp;
        <input type="submit" name="submit" id="submit" value="Go..." />
    </p>
    <p style="font-size: smaller;">
    <?php if ($this->action == 'list') : ?>
        <a href="<?php echo $this->formaction ?>?action=edit"><?php echo $this->getModuleString('LNK_CREATE') ?></a> | 
        <a href="<?php echo $this->formaction ?>?action=metadata">View metadata for <?php echo $this->module ?></a> |
    <?php elseif ($this->action == 'edit' || $this->action == 'detail') : ?>
        <a href="<?php echo $this->formaction ?>">&laquo; Back to list</a> | 
    <?php endif; ?>
        <a href="<?php echo $this->formaction ?>?action=logout">Logout</a>
    </p>
        <?php endif; ?>
    </form>
    <?php echo $this->view ?>
</body>
</html>
