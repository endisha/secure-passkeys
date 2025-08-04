<?php
defined('ABSPATH') || exit;
?>
<div v-cloak>
  <nav class="nav-tab-wrapper">
    <a
      :href="'admin.php?page=secure-passkeys-settings#/'"
      class="nav-tab nav-tab-active"
    >
      <?php esc_html_e('General Settings', 'secure-passkeys'); ?>
    </a>
    <a
      :href="'admin.php?page=secure-passkeys-settings#/display-settings'"
      class="nav-tab"
    >
      <?php esc_html_e('Display Settings', 'secure-passkeys'); ?>
    </a>
    <a
      :href="'admin.php?page=secure-passkeys-settings#/advanced-settings'"
      class="nav-tab"
    >
      <?php esc_html_e('Advanced Settings', 'secure-passkeys'); ?>
    </a>
  </nav>

  <h1>
    <i v-if="isLoading" class="spinner is-active spin"></i>
  </h1>

  <transition name="fade">
    <div class="error" v-if="errorMessage != ''">
      <p>{{ errorMessage }}</p>
    </div>
    <div class="updated" v-if="successMessage != ''">
      <p>{{ successMessage }}</p>
    </div>
  </transition>

  <div :class="{'loading-blur': isLoading}">
    <h2><?php esc_html_e('General Settings', 'secure-passkeys'); ?></h2>
    <p>
      <?php esc_html_e('Configure the Passkey for each user.', 'secure-passkeys'); ?>
    </p>
  </div>

  <table class="form-table" :class="{'loading-blur': isLoading}" width="100%">
    <tbody>
      <tr>
        <th style="width: 200px">
          <label for="name" class="inline-label"
            ><?php esc_html_e('Maximum Passkeys per User', 'secure-passkeys'); ?></label
          >
        </th>
        <td>
          <label for="registration_maximum_passkeys_enabled">
            <input
              name="registration_maximum_passkeys_enabled"
              type="checkbox"
              id="registration_maximum_passkeys_enabled"
              v-model="settings.registration_maximum_passkeys_enabled"
              true-value="1"
              false-value="0"
              :disabled="submitting || isLoading"
            />
            <?php esc_html_e('Enable or disable the restriction for the maximum number of passkeys per user.', 'secure-passkeys'); ?>
            <p class="help">
              <?php esc_html_e('If disabled, users can register an unlimited number of passkeys.', 'secure-passkeys'); ?>
            </p>
          </label>
        </td>
      </tr>
      <tr>
        <th>
          <label
            for="registration_maximum_passkeys_per_user"
            class="inline-label"
          >
            <?php esc_html_e('Number of Passkeys', 'secure-passkeys'); ?>
          </label>
        </th>
        <td>
          <input
            type="number"
            id="registration_maximum_passkeys_per_user"
            class="small-text"
            value="3"
            v-model="settings.registration_maximum_passkeys_per_user"
            :disabled="submitting || isLoading"
          />
          <p class="description">
            <?php esc_html_e('Set the maximum number of passkeys a user can register (if enabled).', 'secure-passkeys'); ?>
          </p>
        </td>
      </tr>
      <tr>
        <th>
          <label for="excluded_roles_registration_login" class="inline-label">
            <?php esc_html_e('Excluded User Roles', 'secure-passkeys'); ?>
          </label>
        </th>
        <td>
          <select
            id="excluded_roles_registration_login"
            name="excluded_roles_registration_login[]"
            class="regular-small"
            v-model="settings.excluded_roles_registration_login"
            :disabled="submitting || isLoading"
            style="width: 200px"
            multiple
          >
            <option
              v-for="(label, key) in defaults.roles || {}"
              :value="key"
              :key="key"
            >
              {{ label }}
            </option>
          </select>
          <p class="description">
            <?php esc_html_e('Select user roles to exclude from passkey registration and login.', 'secure-passkeys'); ?>
          </p>
          <p class="help">
            <?php esc_html_e('Users with these roles will not be able to log in, and the passkey registration form will not appear on the profile or registration shortcode.', 'secure-passkeys'); ?>
          </p>
        </td>
      </tr>
      
      <tr>
        <th>
          <label for="auto_generate_security_key_name" class="inline-label"
            ><?php esc_html_e('Auto Generate Security Key Name', 'secure-passkeys'); ?></label
          >
        </th>
        <td>
          <label for="auto_generate_security_key_name">
            <input
              name="auto_generate_security_key_name"
              type="checkbox"
              id="auto_generate_security_key_name"
              v-model="settings.auto_generate_security_key_name"
              true-value="1"
              false-value="0"
              :disabled="submitting || isLoading"
            />
            <?php esc_html_e('Enable or disable the automatic generation of a security key name.', 'secure-passkeys'); ?>
            <p class="help">
              <?php esc_html_e('If enabled, users will not be prompted to enter a security key name manually when registering a passkey.', 'secure-passkeys'); ?>
            </p>
          </label>
        </td>
      </tr>
    </tbody>
  </table>

  <hr :class="{'loading-blur': isLoading}" />

  <div :class="{'loading-blur': isLoading}">
    <p class="settings-description">
      <?php esc_html_e('Configure the WebAuthn and Passkeys login and registration options., we recommend using the default settings unless you have a good understanding of WebAuthn configuration.', 'secure-passkeys'); ?>
    </p>
  </div>

  <table class="form-table" :class="{'loading-blur': isLoading}" width="100%">
    <tbody>
      <tr>
        <th style="width: 200px">
          <label
            for="registration_exclude_credentials_enabled"
            class="inline-label"
            ><?php esc_html_e('Exclude Existing Credentials', 'secure-passkeys'); ?></label
          >
        </th>
        <td>
          <label for="registration_exclude_credentials_enabled">
            <input
              name="registration_exclude_credentials_enabled"
              type="checkbox"
              id="registration_exclude_credentials_enabled"
              v-model="settings.registration_exclude_credentials_enabled"
              true-value="1"
              false-value="0"
              :disabled="submitting || isLoading"
            />
            <?php esc_html_e('Enable this option to prevent users from registering passkeys that are already registered (Recommended: enabled).', 'secure-passkeys'); ?>
          </label>
        </td>
      </tr>
      <tr>
        <th>
          <label for="registration_timeout" class="inline-label">
            <?php esc_html_e('Registration Timeout', 'secure-passkeys'); ?>
          </label>
        </th>
        <td>
          <input
            type="number"
            id="registration_timeout"
            class="small-text"
            value="3"
            v-model="settings.registration_timeout"
            :disabled="submitting || isLoading"
          />
          <p class="description">
            <?php esc_html_e('Set the expiration timeout for passkey registration (in minutes).', 'secure-passkeys'); ?>
          </p>
        </td>
      </tr>
      <tr>
        <th>
          <label for="login_timeout" class="inline-label">
            <?php esc_html_e('Login Timeout', 'secure-passkeys'); ?>
          </label>
        </th>
        <td>
          <input
            type="number"
            id="login_timeout"
            class="small-text"
            value="5"
            v-model="settings.login_timeout"
            :disabled="submitting || isLoading"
          />
          <p class="description">
            <?php esc_html_e('Set the expiration timeout for passkey login (in minutes).', 'secure-passkeys'); ?>
          </p>
        </td>
      </tr>
      <tr>
        <th style="width: 200px">
          <label
            for="registration_user_verification_enabled"
            class="inline-label"
            ><?php esc_html_e('Enable User Verifications', 'secure-passkeys'); ?></label
          >
        </th>
        <td>
          <label for="registration_user_verification_enabled">
            <input
              name="registration_user_verification_enabled"
              type="checkbox"
              id="registration_user_verification_enabled"
              v-model="settings.registration_user_verification_enabled"
              true-value="1"
              false-value="0"
              :disabled="submitting || isLoading"
            />
            <?php esc_html_e('Choose the level of user verification required during passkey registration (Recommended: enabled).', 'secure-passkeys'); ?>
          </label>
        </td>
      </tr>
      <tr>
        <th>
          <label for="login_user_verification" class="inline-label">
            <?php esc_html_e('Login User Verification', 'secure-passkeys'); ?>
          </label>
        </th>
        <td>
          <select
            id="login_user_verification"
            class="regular-small"
            v-model="settings.login_user_verification"
            :disabled="submitting || isLoading"
          >
            <option value="required">
              <?php esc_html_e('Required', 'secure-passkeys'); ?>
            </option>
            <option value="preferred">
              <?php esc_html_e('Preferred', 'secure-passkeys'); ?>
            </option>
            <option value="discouraged">
              <?php esc_html_e('Discouraged', 'secure-passkeys'); ?>
            </option>
          </select>
          <p class="description">
            <?php esc_html_e('Choose the level of user verification required during passkey login.', 'secure-passkeys'); ?>
          </p>
        </td>
      </tr>
    </tbody>
  </table>

  <table class="form-table" :class="{'loading-blur': isLoading}" width="100%">
    <tbody>
      <tr>
        <th></th>
        <td>
          <button
            @click="updateSettings()"
            type="button"
            class="button button-primary"
            id="submit"
            :disabled="submitting || isLoading"
          >
            <i
              v-if="submitting"
              class="spinner is-active spin spinner-button"
            ></i>
            <span v-if="!submitting"
              ><?php esc_html_e('Save Changes', 'secure-passkeys'); ?></span
            >
            <span v-else
              ><?php esc_html_e('Saving...', 'secure-passkeys'); ?></span
            >
          </button>
        </td>
      </tr>
    </tbody>
  </table>
</div>
