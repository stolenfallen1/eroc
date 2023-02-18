<template>
  <div class="text-center ma-2">
    <v-snackbar
      :style="{ 'margin-top': calcMargin(index) }"
      v-for="(error, index) in data.messages"
      :key="index"
      right
      top
      :color="data.color"
      v-model="isshow"
    >
      {{ error }}
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
  methods: {
    calcMargin(i) {
      console.log(i)
      return i * 50 + "px";
    },
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