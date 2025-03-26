<?php
/**
 * Register template
 *
 * @package SecurePasskeys
 */

defined('ABSPATH') || exit;
?>
<div>
  <h3 class="uk-card-title uk-panel-title">
    {{ i18n.title }}
  </h3>
  <p>{{ i18n.description }}</p>

  <div v-if="error" class="uk-alert-danger" uk-alert>{{ error }}</div>
  <div v-if="success" class="uk-alert-success" uk-alert>{{ success }}</div>

  <div class="uk-padding-small uk-background-muted uk-flex uk-flex-wrap uk-flex-between uk-flex-middle uk-text-right@s">
    <strong>
      {{ i18n.your_passkeys }}
      <template v-if="isPasskeysLoading">
        <div uk-spinner="ratio: 0.5"></div>
      </template>
      <template v-else>
        <span class="uk-text-muted">
          <template v-if="credentials_allowed_count !== null">
            ({{ passkeys?.length ?? 0 }} {{ i18n.out_of }}
            {{ credentials_allowed_count }})
          </template>
          <template v-else> ({{ passkeys?.length ?? 0 }}) </template>
        </span>
      </template>
    </strong>

    <button
      :disabled="isPasskeysLoading || addingPasskey || waitingAddPasskey || showSecurityKeyName || deletingPasskey"
      class="uk-button uk-button-primary uk-button-small uk-float-right"
      @click="addPasskey"
    >
      <template v-if="addingPasskey || waitingAddPasskey">
        <div uk-spinner="ratio: 0.5"></div>
        {{ addingPasskey ? i18n.add_waiting_button : i18n.add_passkey_button }}
      </template>
      <template v-else>
        <span uk-icon="plus"></span> {{ i18n.add_passkey_button }}
      </template>
    </button>
  </div>

  <div v-if="showSecurityKeyName" class="uk-card uk-card-default uk-card-body">
    <Transition>
      <div>
        <p class="uk-text-small">
          <strong>{{ i18n.security_key_name }}</strong
          ><br />
          <span v-html="i18n.security_key_description"></span>
        </p>
        <div class="uk-inline uk-width-1-1">
          <input
            v-model="securityKeyNameInput"
            type="text"
            :disabled="creatingPasskey"
            :placeholder="i18n.security_key_name_placeholder"
            class="uk-input"
            @keyup.enter="createPasskey"
            @input="validateInput"
            autofocus
          />
        </div>
        <div v-if="inputError" class="uk-text-danger uk-margin-small-top">
          {{ inputError }}
        </div>
        <div class="uk-margin-top">
          <button
            class="uk-button uk-button-primary uk-button-small"
            :disabled="creatingPasskey || invaldInput"
            @click="createPasskey"
          >
            <template v-if="!creatingPasskey">
              <span uk-icon="plus"></span> {{ i18n.add_button }}
            </template>
            <template v-else>
              <div uk-spinner="ratio: 0.5"></div>
            </template>
          </button>
          <button
            class="uk-button uk-button-danger uk-button-small uk-margin-small-left"
            :disabled="creatingPasskey"
            @click="cancelPasskey"
          >
            <span uk-icon="close"></span> {{ i18n.cancel_button }}
          </button>
        </div>
      </div>
    </Transition>
  </div>

  <ul class="uk-list">
    <template v-if="!isPasskeysLoading && passkeys?.length > 0">
      <li
        v-for="passkey in passkeys"
        :key="passkey"
        class="uk-flex uk-flex-between uk-padding-small uk-background-muted"
      >
        <div class="uk-flex-auto">
          <span class="Button-content">
            <span class="Button-visual Button-leadingVisual">
              <svg
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
            </span>
          </span>
          <strong>{{ passkey.security_key_name }}</strong>
          <span
            v-if="!passkey.is_active"
            class="uk-label uk-label-danger uk-margin-small-left"
            >{{ i18n.inactive }}</span
          >
          <span v-else class="uk-label uk-label-success uk-margin-small-left">{{
            i18n.active
          }}</span>
          <br />
          <span class="uk-text-small">
            {{ i18n.added_on }}: <strong>{{ passkey.created_at }}</strong>
            <template v-if="passkey.last_used_at">
              <br />
              {{ i18n.last_used }}: <strong>{{ passkey.last_used_at }}</strong>
              <strong v-if="passkey.last_used_on">
                ({{ passkey.last_used_on }})</strong
              >
            </template>
          </span>
        </div>
        <div class="uk-flex-last">
          <span
            v-if="deletingPasskey && deletingPasskeyId === passkey.id"
            uk-spinner="ratio: 0.5"
          ></span>
          <a
            v-else
            href="#"
            @click.prevent="deletePasskey(passkey)"
            class="uk-icon-link uk-icon uk-text-danger"
            uk-icon="trash"
          ></a>
        </div>
      </li>
    </template>
    <li
      v-else-if="!isPasskeysLoading && passkeys?.length === 0"
      class="uk-text-center uk-padding-small uk-background-muted"
    >
      {{ i18n.no_passkeys_found }}
    </li>
    <li
      v-else-if="isPasskeysLoading"
      class="uk-text-center uk-padding-small uk-background-muted"
    >
      <div uk-spinner></div>
    </li>
  </ul>
</div>
