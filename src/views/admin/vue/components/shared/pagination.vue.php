<?php
defined('ABSPATH') || exit;
?>
<div v-if="pages > 1 && count > 0">
  <br />
  <div class="col-sm-3" v-if="count > 0">
    <span
      >{{ count }}
      <?php esc_html_e('records', 'secure-passkeys'); ?>
      /
    </span>
    <?php esc_html_e('Page', 'secure-passkeys'); ?>
    {{ current }}
    <?php esc_html_e('of', 'secure-passkeys'); ?>
    {{ pages }}
  </div>
  <span class="pagination-links">
    <template>
      <a class="button" href="#" @click.prevent="goPage(1)">
        <span class="screen-reader-text"
          ><?php esc_html_e('Previous page', 'secure-passkeys'); ?></span
        >
        <span aria-hidden="true">‹</span>
      </a>
      <a
        href="#"
        :data-page="prevPage"
        @click.prevent="goPage(prevPage)"
        class="button"
      >
        <span aria-hidden="true">&laquo;</span>
      </a>
    </template>
    <template v-if="loopNumbers[0] > 1">
      <span>...</span>
    </template>
    <template v-for="n in loopNumbers">
      <template v-bind:class="{ active: current == n }">
        <a
          href="#"
          :data-page="n"
          @click.prevent="goPage(n)"
          :class="['button', { 'button-primary': current == n }]"
          style="margin-right: 2px"
        >
          {{ n }}
        </a>
      </template>
    </template>
    <template v-if="loopNumbers[loopNumbers.length - 1] < pages">
      <span>...</span>
    </template>
    <template>
      <a
        href="#"
        :data-page="nextPage"
        @click.prevent="goPage(nextPage)"
        class="button"
      >
        <span aria-hidden="true">&raquo;</span>
      </a>
    </template>
  </span>
</div>
