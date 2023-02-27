<template>
  <div style="min-height: 80vh">
    <div>
      <v-speed-dial
        v-if="!hide.includes('floater-btn')"
        v-model="fab"
        bottom
        absolute
        dense
        right
        open-on-hover
        style="bottom: 72px; right: 9px"
      >
        <template v-slot:activator>
          <v-btn v-model="fab" color="#EBEE00" dense fab>
            <v-icon v-if="fab"> mdi-close </v-icon>
            <v-icon v-else> mdi-plus </v-icon>
          </v-btn>
        </template>
        <slot name="custom_fab"></slot>
        <v-btn fab dark small color="indigo" @click="$emit('add')">
          <v-icon>mdi-plus</v-icon>
        </v-btn>
        <v-btn fab dark small color="red" @click="batchDelete(selected)">
          <v-icon>mdi-delete-outline</v-icon>
        </v-btn>
      </v-speed-dial>
    </div>

    <v-toolbar dense flat class="mb-5 py-3" v-if="!hide.includes('headers')">
      <v-toolbar-title>{{ data.title }}</v-toolbar-title>
      <v-divider class="mx-4" inset vertical></v-divider>
      <v-icon @click.stop="$emit('refresh')" v-if="!hide.includes('refresh')">
        mdi-autorenew
        {{ data.loading ? "mdi-spin" : "" }}
      </v-icon>
      <v-spacer></v-spacer>
      <div class="mr-2">
        <v-text-field
          v-model="data.keyword"
          @keydown.enter="$emit('search')"
          @click:append="$emit('search')"
          auto-select-first
          filled
          rounded
          hide-details=""
          :placeholder="'Search'"
          dense
          append-icon="mdi-magnify"
        >
        </v-text-field>
      </div>
      <slot class="mr-2 ml-2" name="generate_btn" />
      <v-btn
        v-if="!hide.includes('add-btn')"
        class="mr-2 ml-2"
        small
        color="primary"
        @click="$emit('add')"
      >
        <v-icon small class="mr-1">mdi-plus</v-icon>
        Add record
      </v-btn>
      <v-menu
        v-if="!hide.includes('filter-btn')"
        offset-y
        left
        nudge-bottom="5"
        :close-on-content-click="false"
      >
        <template v-slot:activator="{ on, attrs }">
          <v-btn class="mr-2" small color="success" v-bind="attrs" v-on="on">
            <v-icon small class="mr-2">mdi-filter-plus-outline</v-icon>
            filter
          </v-btn>
        </template>
        <v-card min-width="300">
          <v-card-text>
            <slot name="custom_filter" />
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
    </v-toolbar>
    <v-data-table
      v-model="tableData.selected"
      :headers="headers"
      :items="tableData.items"
      :single-select="single_select"
      :show-select="show_select"
      :search="data.keyword"
      :server-items-length="tableData.total"
      :options.sync="tableData.options"
      :items-per-page="tableData.options.itemsPerPage"
      @update:options="$emit('fetchPage')"
      @click:row="selectRow"
      :loading="data.loading"
      class="cursor-pointer table-fix-height"
      fixed-header
      :height="height"
      dense
    >
      <template
        v-for="(head, index) of headers"
        v-slot:[`item.${head.value}`]="props"
      >
        <td class="test" :props="props" :key="index">
          <slot :name="head.value" :item="props.item">
            {{ props.item[head.value] || "..." }}
          </slot>
        </td>
      </template>
      <template v-if="!hide.includes('actions')" v-slot:item.action="{ item }">
        <div>
          <slot name="custom-action" :item="item"> </slot>
          <v-icon
            small
            v-if="!hide.includes('edit-btn')"
            color="primary"
            class="mr-1"
            @click="$emit('edit', item)"
          >
            mdi-pencil-outline
          </v-icon>
          <v-icon
            small
            v-if="!hide.includes('delete-btn')"
            color="error"
            class="mr-1"
            @click="remove(item)"
          >
            mdi-delete-outline
          </v-icon>
        </div>
      </template>
    </v-data-table>
  </div>
</template>
<script>
import { mapGetters } from "vuex";
export default {
  data() {
    return {
      selected: [],
      page: 1,
      fab: false,
    };
  },
  props: {
    hide: {
      type: Array,
      default: () => {
        return [];
      },
    },
    data: {
      type: Object,
      default: () => {
        return [];
      },
    },
    tableData: {
      type: Object,
      default: () => {
        return {};
      },
    },
    headers: {
      type: Array,
      default: () => {
        return [];
      },
    },
    searchPlaceholder: {
      type: String,
      default: () => "name, age",
    },
    single_select: {
      type: Boolean,
      default: () => true,
    },
    show_select: {
      type: Boolean,
      default: () => false,
    },
    height: {
      type: String,
      default: () => "60vh",
    },
  },
  methods: {
    selectRow(item, row) {
      row.select(true);
      this.$emit("view", item);
    },
    edit(item) {
      this.$emit("edit", item);
    },
    async remove(val, bypass) {
      this.$emit("remove", val);
    },
    async batchDelete(selected) {
      let ids = selected.map((x) => x.id).toString();
      this.remove(ids);
    },
  },
  computed: {
    ...mapGetters(["drawer"]),
  },
};
</script>
<style lang="scss" scoped>
table .v-data-table-header tr th {
  font-size: 1.7rem !important;
}
.test {
  font-size: 0.7rem !important;
}
</style>