<?php
/**
 * Register template
 *
 * @package SecurePasskeys
 */

defined('ABSPATH') || exit;
?>
<div>
  <h3 class="card-title panel-title">
    {{ i18n.title }}
  </h3>
  <p>{{ i18n.description }}</p>

  <div v-if="error" class="notice error small">{{ error }}</div>
  <div v-if="success" class="notice success small">{{ success }}</div>

  <div class="pb-1 pt-2">
    <div class="row">
      <div class="col-12">
        <strong>
          {{ i18n.your_passkeys }}
          <template v-if="isPasskeysLoading">
            <div class="spinner"></div>
          </template>
          <template v-else>
            <span class="available-passkeys">
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
          class="btn btn-success btn-sm"
          :class="[
                        {
                            'float-left': isRTL,
                            'float-right': !isRTL,
                        },
                    ]"
          @click="addPasskey"
        >
          <template v-if="addingPasskey">
            <div class="spinner"></div>
            {{ i18n.add_waiting_button }}
          </template>
          <template v-else-if="waitingAddPasskey">
            <div class="spinner"></div>
            {{ i18n.add_passkey_button }}
          </template>
          <template v-else>
            <span class="dashicons dashicons-plus"></span>
            {{ i18n.add_passkey_button }}
          </template>
        </button>
      </div>
    </div>
  </div>

  <div v-if="showSecurityKeyName" class="register-passkey-card">
    <Transition>
      <div>
        <p class="small">
          <strong>{{ i18n.security_key_name }}</strong>
          <br />
          <span v-html="i18n.security_key_description"></span>
        </p>
        <div class="input-wrapper">
          <input
            v-model="securityKeyNameInput"
            type="input"
            :disabled="creatingPasskey"
            :placeholder="i18n.security_key_name_placeholder"
            class="input-add"
            @keyup.enter="createPasskey"
            @input="validateInput"
            autofocus
          />
          <div v-if="inputError" class="text-danger input-error">
            {{ inputError }}
          </div>
          <button
            class="btn btn-success btn-sm"
            :disabled="creatingPasskey || invaldInput"
            @click="createPasskey"
          >
            <template v-if="!creatingPasskey">
              <span class="dashicons dashicons-plus"></span>
              {{ i18n.add_button }}
            </template>
            <template v-else>
              <div class="spinner"></div>
            </template>
          </button>
          <button
            class="btn btn-danger btn-sm"
            :disabled="creatingPasskey"
            @click="cancelPasskey"
          >
            <span class="dashicons dashicons-remove"></span>
            {{ i18n.cancel_button }}
          </button>
        </div>
      </div>
    </Transition>
  </div>

  <div class="list-group flex-fill mt-3" style="display: block">
    <template v-if="!isPasskeysLoading && passkeys?.length > 0">
      <div
        :class="[
                    {
                        'is-greyed': isPasskeysLoading || addingPasskey || showSecurityKeyName,
                    },
                ]"
      >
        <div
          v-for="passkey in passkeys"
          :key="passkey"
          class="list-group-item d-flex justify-content-between align-items-center p-2 first-item-second-items"
        >
          <div>
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
              class="label label-danger label-xs"
              v-if="!passkey.is_active"
              >{{ i18n.inactive }}</span
            >
            <span class="label label-success label-xs" v-else>{{
              i18n.active
            }}</span>
            <br />
            <span class="small">
              {{ i18n.added_on }}: <strong>{{ passkey.created_at }}</strong>
              <template v-if="passkey.last_used_at">
                <br />
                {{ i18n.last_used }}:
                <strong>{{ passkey.last_used_at }}</strong>
                <strong v-if="passkey.last_used_on">
                  ({{ passkey.last_used_on }})</strong
                >
              </template>
            </span>
          </div>
          <div>
            <button
              class="btn btn-danger btn-xs btn-delete-passkey"
              :disabled="deletingPasskey"
              @click="deletePasskey(passkey)"
            >
              <div
                class="spinner"
                v-if="deletingPasskey && deletingPasskeyId === passkey.id"
              ></div>
              <span class="dashicons dashicons-trash" v-else></span>
            </button>
          </div>
        </div>
      </div>
    </template>

    <div
      v-else-if="!isPasskeysLoading && passkeys?.length === 0"
      class="list-group-item d-flex justify-content-between align-items-center p-2 first-item-second-items"
    >
      <p class="small no-margin">{{ i18n.no_passkeys_found }}</p>
    </div>

    <div
      v-else-if="isPasskeysLoading"
      class="list-group-item d-flex justify-content-between align-items-center p-2 first-item-second-items"
    >
      <div class="small no-margin">
        <div class="spinner"></div>
      </div>
    </div>
  </div>
</div>
