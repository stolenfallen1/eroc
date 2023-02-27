<template>
  <div>
    <v-list nav>
      <v-list-item dense>
        <v-list-item-title>
          <v-menu
            v-if="!hide.includes('filter')"
            offset-y
            left
            nudge-left="190"
            nudge-top="50"
            :close-on-content-click="false"
          >
            <template v-slot:activator="{ on, attrs }">
              <v-btn
                width="100%"
                small
                color="success"
                v-bind="attrs"
                v-on="on"
              >
                <v-icon small class="mr-2">mdi-filter-plus-outline</v-icon>
                filter
              </v-btn>
            </template>
            <v-card min-width="300">
              <v-card-text>
                <slot name="side_filter" />
              </v-card-text>
              <v-card-actions>
                <v-spacer></v-spacer>
                <v-btn color="error" text @click="$emit('resetFilters')">
                  {{ "reset" }}
                </v-btn>
                <v-btn color="primary" depressed @click="$emit('filterRecord')">
                  {{ "filter" }}
                </v-btn>
              </v-card-actions>
            </v-card>
          </v-menu>
        </v-list-item-title>
      </v-list-item>
      <v-list-item dense v-if="!hide.includes('add')">
        <v-list-item-title>
          <v-btn :disabled="!user.warehouse" @click="$emit('add')" class="mt-2" width="100%" small color="primary">
            <v-icon class="mr-2" small>mdi-plus</v-icon>
            Add Record
          </v-btn>
        </v-list-item-title>
      </v-list-item>
      <v-list-item class="mt-2" dense v-if="!hide.includes('edit')">
        <v-list-item-title>
          <v-btn :disabled="disabled.includes('edit')" @click="$emit('edit')" width="100%" small color="warning">
            <v-icon class="mr-2" small>mdi-pencil</v-icon>
            Edit Record
          </v-btn>
        </v-list-item-title>
      </v-list-item>
      <v-list-item v-if="!hide.includes('delete')" class="mt-2" dense>
        <v-list-item-title>
          <v-btn @click="$emit('delete')" :disabled="disabled.includes('delete')" width="100%" small color="error">
            <v-icon class="mr-2" small>mdi-delete</v-icon>
            Remove Record
          </v-btn>
        </v-list-item-title>
      </v-list-item>
      <v-list-item v-if="!hide.includes('approve')" class="mt-2" dense>
        <v-list-item-title>
          <slot name="side-actions" ></slot>
        </v-list-item-title>
      </v-list-item>
    </v-list>
  </div>
</template>
<script>
import { mapGetters } from "vuex"
export default {
  props:{
    hide: {
      type: Array,
      default: () => {
        return [];
      },
    },
    disabled: {
      type: Array,
      default: () => {
        return [];
      },
    },
  },
  data() {
    return {};
  },
  computed:{
    ...mapGetters(["user"])
  }
};
</script>