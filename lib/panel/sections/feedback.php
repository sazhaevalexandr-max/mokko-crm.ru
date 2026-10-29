<?php if (!defined('MOKKO')) { die; } ?>
<div class="layer" id="feedback">
<style>
#feedback .feedback-empty{display:flex;flex-direction:column;align-items:center;justify-content:center;gap:20px;min-height:60vh;padding:24px 16px;text-align:center;}
#feedback .feedback-empty video{width:auto;max-width:100%;max-height:360px;border-radius:16px;box-shadow:0 12px 44px rgba(0,0,0,.45);display:block;}
#feedback .feedback-empty h3{margin:0;max-width:540px;font-weight:600;line-height:1.45;}
</style>
<div class="feedback-empty">
<video src="<?= BASE_PATH ?>/images/support-cat.mp4?v=1" poster="<?= BASE_PATH ?>/images/support-cat.jpg?v=1" autoplay muted loop playsinline preload="metadata"></video>
<h3>тут пока ничего нет Т_Т</h3>
</div>
</div>

