<?php
defined('ABSPATH') || exit;
?>
<div v-cloak>
  <h1>
    <i v-if="isLoading" class="spinner is-active spin"></i>
  </h1>

  <transition name="fade">
    <div class="error" v-if="errorMessage != ''">
      <p>{{ errorMessage }}</p>
    </div>
  </transition>

  <h2><?php esc_html_e('Overview', 'secure-passkeys'); ?></h2>

  <div class="row">
    <div class="col-md-12">
      <p class="overview-description">
        <?php esc_html_e(
            'Secure Passkeys is a passwordless authentication solution based on WebAuthn, enabling users to log in using device-based methods like biometrics or PIN. It eliminates the need for traditional passwords, enhancing security and and upgrade your WordPress site with Secure Passkeys for a modern, secure, and efficient authentication experience.',
            'secure-passkeys'
        ); ?>
      </p>
    </div>
  </div>

  <hr :class="{'loading-blur': isLoading}" />

  <div class="row mt-15">
    <div class="col-md-4">
      <h3><?php esc_html_e('Statistics', 'secure-passkeys'); ?></h3>

      <div
        class="overview-widget-block status-badge-primary clearfix"
        :class="{'loading-blur': isLoading}"
      >
        <div class="icon">
          <i class="dashicons dashicons-admin-users"></i>
        </div>
        <div class="detail">
          <span class="count">
            {{ data?.users_count }}
          </span>
          <span class="desc"
            ><?php esc_html_e('Users', 'secure-passkeys'); ?></span
          >
        </div>
      </div>

      <div
        class="overview-widget-block status-badge-green clearfix mt-10"
        :class="{'loading-blur': isLoading}"
      >
        <div class="icon">
          <svg
            style="fill: rgb(255, 255, 255); height: 40px; width: 40px"
            aria-hidden="true"
            height="16"
            viewBox="0 0 16 16"
            version="1.1"
            width="16"
            class="octicon octicon-passkey-fill color-fg-inherit"
          >
            <path
              d="M2.743 4.757a3.757 3.757 0 1 1 5.851 3.119 5.991 5.991 0 0 1 2.15 1.383c.17.17.257.405.258.646.003.598.001 1.197 0 1.795L11 12.778v.721a.5.5 0 0 1-.5.5H1.221a.749.749 0 0 1-.714-.784 6.004 6.004 0 0 1 3.899-5.339 3.754 3.754 0 0 1-1.663-3.119Z"
            ></path>
            <path
              d="M15.75 6.875c0 .874-.448 1.643-1.127 2.09a.265.265 0 0 0-.123.22v.59c0 .067-.026.13-.073.177l-.356.356a.125.125 0 0 0 0 .177l.356.356c.047.047.073.11.073.176v.231c0 .067-.026.13-.073.177l-.356.356a.125.125 0 0 0 0 .177l.356.356c.047.047.073.11.073.177v.287a.247.247 0 0 1-.065.168l-.8.88a.52.52 0 0 1-.77 0l-.8-.88a.247.247 0 0 1-.065-.168V9.185a.264.264 0 0 0-.123-.22 2.5 2.5 0 1 1 3.873-2.09ZM14 6.5a.75.75 0 1 0-1.5 0 .75.75 0 0 0 1.5 0Z"
            ></path>
          </svg>
        </div>
        <div class="detail">
          <span class="count">
            {{ data?.passkeys_count }}
          </span>
          <span class="desc"
            ><?php esc_html_e('Passkeys', 'secure-passkeys'); ?></span
          >
        </div>
      </div>
      <div
        class="overview-widget-block status-badge-cyan clearfix mt-10"
        :class="{'loading-blur': isLoading}"
      >
        <div class="icon">
          <i class="dashicons dashicons-menu"></i>
        </div>
        <div class="detail">
          <span class="count">
            {{ data?.logs_count }}
          </span>
          <span class="desc"
            ><?php esc_html_e('Activities', 'secure-passkeys'); ?></span
          >
        </div>
      </div>

      <div
        class="overview-widget-block status-badge-pink clearfix mt-10"
        :class="{'loading-blur': isLoading}"
      >
        <div class="icon">
          <i class="dashicons dashicons-shield"></i>
        </div>
        <div class="detail">
          <span class="count">
            {{ data?.challenges_count }}
          </span>
          <span class="desc"
            ><?php esc_html_e('Challenges', 'secure-passkeys'); ?></span
          >
        </div>
      </div>
    </div>

    <div class="col-md-4">
      <h3>
        <?php esc_html_e('Overview of Authenticators', 'secure-passkeys'); ?>
      </h3>
      <div class="panel panel-default">
        <div class="panel-body">
          <div class="list-group-empty" v-if="isLoading">
            <i
              class="spinner is-active spin"
              style="text-align: center; margin: auto"
            ></i>
          </div>

          <div
            class="list-group-empty"
            v-if="!isLoading && data.authenticators.length == 0"
          >
            <h3>
              <?php esc_html_e('No authenticators registered.', 'secure-passkeys'); ?>
            </h3>
          </div>

          <div
            class="list-group scrollable-list"
            v-if="!isLoading && data.authenticators.length != 0"
          >
            <div
              class="list-group-item"
              v-for="authenticator in data.authenticators"
              :key="authenticator.name"
            >
              <span class="float-end">
                <img
                  v-if="authenticator.icon"
                  :src="authenticator.icon"
                  style="width: 30px"
                />
                <i
                  class="dashicons dashicons-question"
                  style="font-size: 30px; color: #a8a8a8"
                  v-else
                ></i>
              </span>
              <div class="authenticator-large-name">
                {{ authenticator.name }}
              </div>
              <div class="authenticator-large-count">
                {{ authenticator.count }}
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-md-4">
      <h3><?php esc_html_e('Last Login Activity', 'secure-passkeys'); ?></h3>
      <div class="panel panel-default">
        <div class="panel-body">
          <div class="list-group-empty" v-if="isLoading">
            <i
              class="spinner is-active spin"
              style="text-align: center; margin: auto"
            ></i>
          </div>

          <div
            class="list-group-empty"
            v-if="!isLoading && data.last_login_activity.length == 0"
          >
            <h3><?php esc_html_e('No activities.', 'secure-passkeys'); ?></h3>
          </div>

          <div
            class="list-group scrollable-list"
            v-if="!isLoading && data.last_login_activity.length != 0"
          >
            <div
              class="list-group-item"
              v-for="activity in data.last_login_activity"
              :key="activity.name"
            >
              <span class="username">
                <a
                  :href="activity.user.page_url"
                  target="_blank"
                  v-if="activity.user.name"
                >
                  {{ activity.user.name }}
                </a>
                <template v-else>
                  <?php esc_html_e('User ID', 'secure-passkeys'); ?>:
                  {{ activity.user_id }}
                </template>
              </span>
              <template v-if="activity.aaguid">
                <div class="authenticator-name">
                  <img
                    v-if="activity.aaguid.icon"
                    :src="activity.aaguid.icon"
                    style="width: 15px"
                  />
                  <i
                    class="dashicons dashicons-question"
                    style="font-size: 15px; color: #a8a8a8"
                    v-else
                  ></i>
                  <span style="vertical-align: text-bottom">{{
                    activity.aaguid.name
                  }}</span>
                </div>
              </template>
              <span>{{ activity.ip_address }}</span>
              <br />
              <span>
                {{ activity.created_at }}
                <template v-if="activity.login_on">
                  | {{ activity.login_on }}
                </template>
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
