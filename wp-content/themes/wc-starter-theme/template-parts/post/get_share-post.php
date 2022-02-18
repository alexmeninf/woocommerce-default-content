<?php

// Exit if accessed directly
if (!defined('ABSPATH'))
  exit;

?>

<button class="mt-4 btn-theme btn-small btn-theme-inverse" type="button" data-bs-toggle="collapse" data-bs-target="#collapseShare" aria-expanded="false" aria-controls="collapseShare">
  <i class="fal fa-share"></i> Compartilhe
</button>

<div class="collapse" id="collapseShare">
  <div class="share-post">
    <ul>
      <li>
        <a class="share-facebook" href="https://www.facebook.com/sharer/sharer.php?u=<?php the_permalink() ?>&redirect_uri=<?php the_permalink() ?>" target="_blank" rel="noopener" aria-label="Share button" title="<?php _e('Compartilhe no Facebook', 'wcstartertheme') ?>">
          <i class="fab fa-facebook-f"></i>
        </a>
      </li>
      <li>
        <a class="share-twitter" href="https://twitter.com/intent/tweet?text=<?php the_title() ?>%2E.%20Assista%20em%3A%20&tw_p=tweetbutton&url=<?php the_permalink() ?>" target="_blank" rel="noopener" aria-label="Share button" title="<?php _e('Compartilhe no Twitter', 'wcstartertheme') ?>">
          <i class="fab fa-twitter"></i>
        </a>
      </li>
      <li>
        <a class="share-linkedin" href="https://www.linkedin.com/shareArticle?mini=true&url=<?php the_permalink() ?>&title=&summary=<?php the_title() ?>&source=" target="_blank" rel="noopener" aria-label="Share button" title="<?php _e('Compartilhe no Linkedin', 'wcstartertheme') ?>">
          <i class="fab fa-linkedin-in"></i>
        </a>
      </li>
      <li>
        <a class="share-whatsapp" href="https://api.whatsapp.com/send?phone=&text=<?php the_title() ?>.%20Assista%20em%3A%20<?php the_permalink() ?>" target="_blank" rel="noopener" aria-label="Share button" title="<?php _e('Compartilhe no WhatsApp', 'wcstartertheme') ?>">
          <i class="fab fa-whatsapp"></i>
        </a>
      </li>
      <li>
        <a class="share-telegram" href="https://telegram.me/share/url?url=<?php the_permalink() ?>&text=<?= the_title() ?>" target="_blank" rel="noopener" aria-label="Share button" title="<?php _e('Compartilhe no Telegram', 'wcstartertheme') ?>">
          <i class="fab fa-telegram-plane"></i>
        </a>
      </li>
      <li>
        <button type="button" class="share-default share-button" aria-label="Share button" title="<?php _e('Compartilhe nos apps', 'wcstartertheme') ?>"><i class="fal fa-share-alt"></i></button>
      </li>
    </ul>
  </div>
</div>