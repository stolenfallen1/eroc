<template>
  <div class="text-center ma-2">
    <v-snackbar right top :color="data.color" v-model="isshow">
      {{ data.message }}
      <template v-slot:action="{ attrs }">
        <v-btn
          small
          :color="data.color == 'error' ? 'white' : 'error'"
          text
          v-bind="attrs"
          @click="$emit('close')"
        >
          Close
        </v-btn>
      </template>
    </v-snackbar>
  </div>
</template>
<script>
export default {
  props: {
    data: {
      type: Object,
      default: () => {},
    },
    show: {
      type: Boolean,
      default: () => false,
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
    isshow: {
      handler(val) {
        if (!val) this.$emit("close");
      },
    },
  },
};
</script>