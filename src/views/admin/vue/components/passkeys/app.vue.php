<?php
defined('ABSPATH') || exit;
?>
<div v-cloak>
  <h1>
    <?php esc_html_e('Passkeys', 'secure-passkeys'); ?>
  </h1>
  <p>
    <?php esc_html_e('See the list of passkeys that users have created.', 'secure-passkeys'); ?>
  </p>

  <transition name="fade">
    <div class="error" v-if="missingNonce || errorMessage != ''">
      <p>
        {{ errorMessage }}
      </p>
    </div>
  </transition>

  <transition name="fade">
    <div class="updated" v-if="successMessage != ''">
      <p>
        {{ successMessage }}
      </p>
    </div>
  </transition>

  <hr />

  <div v-if="!missingNonce">
    <div class="tablenav">
      <div class="alignleft removealignleft">
        <label for="user_id" class="inline-label">
          <?php esc_html_e('User', 'secure-passkeys'); ?>
        </label>

        <input
          type="text"
          id="user_id"
          style="width: 250px"
          class="form-control user_id"
          placeholder="<?php echo esc_attr__('User ID, Name, or Email', 'secure-passkeys'); ?>"
        />
      </div>

      <div class="alignleft">
        <label for="is_active" class="inline-label">
          <?php esc_html_e('Status', 'secure-passkeys'); ?>
        </label>

        <select
          v-model="filters.is_active"
          class="form-control"
          id="is_active"
          @keyup.enter="filterList"
        >
          <option value="">
            <?php esc_html_e('Any', 'secure-passkeys'); ?>
          </option>
          <option value="1">
            <?php esc_html_e('Activated', 'secure-passkeys'); ?>
          </option>
          <option value="0">
            <?php esc_html_e('Deactivated', 'secure-passkeys'); ?>
          </option>
        </select>
      </div>

      <div class="alignleft">
        <label for="authenticator" class="inline-label">
          <?php esc_html_e('Authenticator', 'secure-passkeys'); ?>
        </label>

        <select
          id="authenticator"
          class="form-control"
          style="min-width: 200px"
          v-model="filters.aaguid"
        >
          <option value="" selected="">
            <?php esc_html_e('Any', 'secure-passkeys'); ?>
          </option>
          <option
            v-for="(authenticator, key) in authenticators"
            :key="key"
            :value="key"
          >
            {{ authenticator.name }} ({{ authenticator.count }})
          </option>
        </select>
      </div>

      <div class="alignleft">
        <label for="created_at" class="inline-label">
          <?php esc_html_e('Created Date', 'secure-passkeys'); ?>
        </label>

        <input
          type="text"
          v-model="filters.created_at"
          id="created_at"
          class="form-control created_at"
          @keyup.enter="filterList()"
        />
      </div>

      <div class="alignleft">
        <label for="last_used_at" class="inline-label">
          <?php esc_html_e('Last Used Date', 'secure-passkeys'); ?>
        </label>

        <input
          type="text"
          v-model="filters.last_used_at"
          id="last_used_at"
          class="form-control last_used_at"
          @keyup.enter="filterList()"
        />
      </div>

      <div class="alignleft">
        <button class="button" type="button" @click="filterList()">
          <span
            :class="{'spinner is-active spin spinner-button': isLoading}"
          ></span>
          <?php esc_html_e('Filter', 'secure-passkeys'); ?>
        </button>
        <button
          class="button button-reset"
          type="button"
          @click="filterReset()"
          v-show="hasFilters"
        >
          <?php esc_html_e('Reset', 'secure-passkeys'); ?>
        </button>
      </div>
    </div>

    <hr />

    <div>
      <b class="table-count"
        ><?php esc_html_e('Count', 'secure-passkeys'); ?>:
        {{ count < 0 ? "" : count }}
        <i v-if="count < 0" class="spinner is-active spin spinner-button"></i
      ></b>
    </div>

    <table
      class="wp-list-table passkeys widefat fixed striped table-view-list"
      width="100%"
    >
      <thead>
        <tr>
          <th class="manage-column column-id">
            <?php esc_html_e('#', 'secure-passkeys'); ?>
          </th>
          <th class="manage-column column-user">
            <?php esc_html_e('User', 'secure-passkeys'); ?>
          </th>
          <th class="manage-column column-security_key_name">
            <?php esc_html_e('Security Key Name', 'secure-passkeys'); ?>
          </th>
          <th class="manage-column column-authenticator_name">
            <?php esc_html_e('Authenticator', 'secure-passkeys'); ?>
          </th>
          <th class="manage-column column-is_active">
            <?php esc_html_e('Active', 'secure-passkeys'); ?>
          </th>
          <th class="manage-column column-last_used">
            <?php esc_html_e('Last Used', 'secure-passkeys'); ?>
          </th>
          <th class="manage-column column-created_at">
            <?php esc_html_e('Created Date', 'secure-passkeys'); ?>
          </th>
          <th class="manage-column column-actions">
            <?php esc_html_e('Actions', 'secure-passkeys'); ?>
          </th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="item in list" :key="item.id" v-if="!isLoading">
          <td data-label="<?php esc_html_e('#', 'secure-passkeys'); ?>">
            {{ item.id }}
          </td>
          <td data-label="<?php esc_html_e('User', 'secure-passkeys'); ?>">
            <template v-if="item.user">
              <a :href="item.user.page_url" target="_blank">{{
                item.user?.name ?? item.user_id
              }}</a>
            </template>
            <template v-else>
              {{ item.user_id ?? "-" }}
            </template>
          </td>
          <td
            data-label="<?php esc_html_e('Security Key Name', 'secure-passkeys'); ?>"
          >
            {{ item.security_key_name }}
          </td>
          <td
            data-label="<?php esc_html_e('Authenticator', 'secure-passkeys'); ?>"
          >
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
          <td data-label="<?php esc_html_e('Active', 'secure-passkeys'); ?>">
            <span
              class="dashicons dashicons-yes text-active"
              v-if="item.is_active"
            ></span>
            <span class="dashicons dashicons-no text-inactive" v-else></span>
          </td>
          <td data-label="<?php esc_html_e('Last Used', 'secure-passkeys'); ?>">
            {{ item.last_used_at ?? "-" }}
            <template v-if="item.last_used_on">
              <span class="label label-last-used-on">{{
                item.last_used_on
              }}</span>
            </template>
          </td>
          <td
            data-label="<?php esc_html_e('Created Date', 'secure-passkeys'); ?>"
          >
            {{ item.created_at }}
          </td>
          <td data-label="<?php esc_html_e('Actions', 'secure-passkeys'); ?>">
            <template>
              <a
                href="#"
                @click.prevent="_activate(item.id, false)"
                v-if="!item.is_active && !actionProcessing"
              >
                <?php esc_html_e('Activate', 'secure-passkeys'); ?>
              </a>
              <a
                href="#"
                @click.prevent="_activate(item.id, true)"
                v-if="item.is_active && !actionProcessing"
              >
                <?php esc_html_e('Deactivate', 'secure-passkeys'); ?>
              </a>
              <span v-if="actionProcessing && actionProcessingId == item.id">
                <?php esc_html_e('Processing...', 'secure-passkeys'); ?>
              </span>
              <span v-if="actionProcessing && actionProcessingId != item.id">
                <template v-if="!item.is_active">
                  <?php esc_html_e('Activate', 'secure-passkeys'); ?>
                </template>
                <template v-else>
                  <?php esc_html_e('Deactivate', 'secure-passkeys'); ?>
                </template>
              </span>
            </template>
            |
            <span>
              <template>
                <a href="#" @click.prevent="_delete(item.id)" v-if="!deleting">
                  <?php esc_html_e('Delete', 'secure-passkeys'); ?>
                </a>
                <span v-if="deleting && deletingId == item.id">
                  <?php esc_html_e('Deleting...', 'secure-passkeys'); ?>
                </span>
              </template>
            </span>
          </td>
        </tr>
        <tr v-if="list.length == 0 && !isLoading">
          <td colspan="8" class="center no-label">
            <?php esc_html_e('No records found.', 'secure-passkeys'); ?>
          </td>
        </tr>
        <tr v-if="isLoading">
          <td colspan="8" class="center no-label" style="text-align: center">
            <i
              class="spinner is-active spin"
              style="text-align: center; margin: auto"
            ></i>
          </td>
        </tr>
      </tbody>
    </table>

    <paginate
      :count="count"
      :pages="pages"
      :current="current"
      @navigate="loadPage"
    ></paginate>
  </div>
</div>
