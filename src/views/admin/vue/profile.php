<?php

defined('ABSPATH') || exit;
?>
<div>
  <table class="form-table secure-passkeys">
    <tbody>
      <tr>
        <th>
          <label>{{ i18n.passkey_label }}</label>
        </th>
        <td>
          <div class="description">{{ i18n.description }}</div>

          <div v-if="errorMessage" class="error">
            <p>{{ errorMessage }}</p>
          </div>

          <div v-if="successMessage" class="updated">
            <p>{{ successMessage }}</p>
          </div>

          <button
            :disabled="isLoading || addingPasskey || waitingAddPasskey || showSecurityKeyName || deletingPasskey"
            class="button add"
            v-if="is_owner"
            @click.prevent="addPasskey"
            @keydown.enter.prevent
          >
            <template v-if="addingPasskey">
              <i class="spinner is-active spin spinner-button"></i>
              {{ i18n.add_waiting_button }}
            </template>
            <template v-else-if="waitingAddPasskey">
              <i class="spinner is-active spin spinner-button"></i>
              {{ i18n.add_passkey_button }}
            </template>
            <template v-else>
              <span
                class="dashicons dashicons-plus"
                style="vertical-align: middle"
              ></span>
              {{ i18n.add_passkey_button }}
            </template>
          </button>

          <div
            :class="[
                          {
                              'your-passkeys-text': is_owner,
                              'your-passkeys-text remove-margin': !is_owner,
                          },
                      ]"
          >
            <template v-if="is_owner">
              {{ i18n.your_passkeys }}
            </template>
            <template v-else>
              {{ i18n.user_passkeys }}
            </template>

            <template v-if="isLoading">
              <i class="spinner is-active spin spinner-button"></i>
            </template>
            <template v-else>
              <span class="available-passkeys">
                <template v-if="credentials_allowed_count !== null">
                  (<strong>{{ list?.length ?? 0 }}</strong> {{ i18n.out_of }}
                  <strong>{{ credentials_allowed_count }}</strong
                  >)
                </template>
                <template v-else>
                  (<strong>{{ list?.length ?? 0 }}</strong
                  >)</template
                >
              </span>
            </template>
          </div>

          <div>
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
                      class="regular-text"
                      @keyup.enter="createPasskey"
                      @input="validateInput"
                      autofocus
                    />
                    <button
                      class="button margin-right"
                      :disabled="creatingPasskey || invaldInput"
                      @click="createPasskey"
                    >
                      <template v-if="!creatingPasskey">
                        <span class="dashicons dashicons-plus"></span>
                        {{ i18n.add_button }}
                      </template>
                      <template v-else>
                        <div class="spinner is-active spin"></div>
                      </template>
                    </button>
                    <button
                      class="button button-cancel"
                      :disabled="creatingPasskey"
                      @click="cancelPasskey"
                    >
                      <span class="dashicons dashicons-remove"></span>
                      {{ i18n.cancel_button }}
                    </button>
                    <div v-if="inputError" class="text-danger input-error">
                      {{ inputError }}
                    </div>
                  </div>
                </div>
              </Transition>
            </div>
          </div>

          <div>
            <table
              class="wp-list-table passkeys widefat striped table-view-list"
              width="100%"
            >
              <thead>
                <tr>
                  <th>{{ i18n.security_key_name_column }}</th>
                  <th>{{ i18n.authenticator_column }}</th>
                  <th>{{ i18n.active_column }}</th>
                  <th>{{ i18n.last_used_column }}</th>
                  <th>{{ i18n.created_at_column }}</th>
                  <th v-if="is_owner || has_access">
                    {{ i18n.actions_column }}
                  </th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="item in list" :key="item.id" v-if="!isLoading">
                  <td :data-label="i18n.security_key_name_column">
                    {{ item.security_key_name }}
                  </td>
                  <td :data-label="i18n.authenticator_column">
                    <template v-if="item.aaguid">
                      <img
                        :src="item.aaguid.icon"
                        style="width: 15px"
                        class="authenticator-icon"
                      />
                      {{ item.aaguid.name }}
                    </template>
                    <template v-else> - </template>
                  </td>
                  <td :data-label="i18n.active_column">
                    <span
                      class="dashicons dashicons-yes text-active"
                      v-if="item.is_active"
                    ></span>
                    <span
                      class="dashicons dashicons-no text-inactive"
                      v-else
                    ></span>
                  </td>
                  <td :data-label="i18n.last_used_column">
                    {{ item.last_used_at || "-" }}
                    <template v-if="item.last_used_on">
                      <span class="label label-last-used-on">{{
                        item.last_used_on
                      }}</span>
                    </template>
                  </td>
                  <td :data-label="i18n.created_at_column">
                    {{ item.created_at }}
                  </td>
                  <td
                    :date-label="i18n.actions_column"
                    v-if="is_owner || has_access"
                  >
                    <a
                      href="#"
                      @click.prevent="deletePasskey(item.id)"
                      v-if="!deletingPasskey"
                    >
                      {{ i18n.delete }}
                    </a>
                    <span v-if="deletingPasskey && deletingId == item.id">
                      {{ i18n.deleting }}
                    </span>

                    <template v-if="!is_owner && has_access">
                      |
                      <a
                        href="#"
                        @click.prevent="activateDeactivatePasskey(item.id, false)"
                        v-if="!item.is_active && !actionProcessing"
                      >
                        <?php esc_html_e('Activate', 'secure-passkeys'); ?>
                      </a>
                      <a
                        href="#"
                        @click.prevent="activateDeactivatePasskey(item.id, true)"
                        v-if="item.is_active && !actionProcessing"
                      >
                        <?php esc_html_e('Deactivate', 'secure-passkeys'); ?>
                      </a>
                      <span
                        v-if="actionProcessing && actionProcessingId == item.id"
                      >
                        <?php esc_html_e('Processing...', 'secure-passkeys'); ?>
                      </span>
                      <span
                        v-if="actionProcessing && actionProcessingId != item.id"
                      >
                        <template v-if="!item.is_active">
                          <?php esc_html_e('Activate', 'secure-passkeys'); ?>
                        </template>
                        <template v-else>
                          <?php esc_html_e('Deactivate', 'secure-passkeys'); ?>
                        </template>
                      </span>
                    </template>
                  </td>
                </tr>
                <tr v-if="list.length == 0 && !isLoading">
                  <td :colspan="is_owner || has_access ? 6 : 5" class="center">
                    {{ i18n.no_records_found }}
                  </td>
                </tr>
                <tr v-if="isLoading">
                  <td
                    :colspan="is_owner || has_access ? 6 : 5"
                    class="center warning"
                    style="text-align: center"
                  >
                    <i
                      class="spinner is-active spin"
                      style="text-align: center; margin: auto"
                    ></i>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </td>
      </tr>
    </tbody>
  </table>
</div>
