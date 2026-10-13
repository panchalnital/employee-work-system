<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Workday • Employee work updates</title><link rel="stylesheet" href="style.css"></head><body>
<header><a class="brand" href="?page=dashboard">▦ Workday</a><span>Work updates & performance</span><?php if($user): ?><div><?=e($user['name'])?> · <?=e($user['role'])?></div><form method="post"><?=csrf()?><input type="hidden" name="action" value="logout"><button class="secondary">Sign out</button></form><?php endif ?></header>
<?php if($user): ?><nav><?php foreach(['dashboard'=>'Overview','tasks'=>'Tasks','updates'=>'Daily updates']+($manager?['reports'=>'Performance']:[]) as $key=>$label): ?><a class="<?=$page===$key?'active':''?>" href="?page=<?=$key?>"><?=$label?></a><?php endforeach ?></nav><?php endif ?>
<main><?php if($error): ?><p class="alert" role="alert"><?=e($error)?></p><?php endif ?><?php if($flash): ?><p class="notice"><?=e($flash)?></p><?php endif ?>
<?php require $template; ?>
</main><footer>Workday · Built for clear, accountable teamwork</footer></body></html>
