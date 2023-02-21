<template>
  <dialog transition="dialog-bottom-transition" max-width="900">
    <v-card>
      <v-text-field>

      </v-text-field>
      
    </v-card>
  </dialog>
</template>
<script>
export default {
  props: {
    files: {
      type: Array,
      default: () => {
        return [];
      },
    },
  },
};
</script>