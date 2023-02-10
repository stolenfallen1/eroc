<template>
  <v-dialog
    v-model="isshow"
    hide-overlay
    width="950"
    transition="dialog-bottom-transition"
    scrollable
  >
    <v-card tile>
      <v-toolbar flat dark color="primary">
        <v-toolbar-title>Purchase request</v-toolbar-title>
        <v-spacer></v-spacer>
        <v-btn icon dark @click="isshow = false">
          <v-icon>mdi-close</v-icon>
        </v-btn>
      </v-toolbar>
      <v-card-text>
        <v-container fluid>
          <v-row no-gutters>
            <v-cols sm="12" md="6" xl="4">
              <v-autocomplete v-model="payload.item"></v-autocomplete>
            </v-cols>
            <v-cols sm="12" md="6" xl="4"> ssssss </v-cols>
          </v-row>
        </v-container>
      </v-card-text>

      <div style="flex: 1 1 auto"></div>
    </v-card>
  </v-dialog>
</template>
<script>
export default {
  props: {
    show: {
      type: Boolean,
      default: () => false,
    },
    payload: {
      type: Object,
      default: () => {},
    },
  },
  data() {
    return {
      isshow: false,
    };
  },
  watch: {
    show: {
      handler(val) {
        this.isshow = val;
      },
    },
  },
};
</script>