<template>
  <v-dialog v-model="show" persistent width="500">
    <v-card elevation="1">
      <v-card-title>Denied remarks</v-card-title>
      <v-card-text>
        <p class="ma-0 pa-0">Remarks</p>
        <v-textarea v-model="payload.remarks" solo dense>
        </v-textarea>
        <div class="d-flex flex-row-reverse">
          <v-btn small @click="$emit('submit', payload.remarks)" color="primary">Submit</v-btn>
          <v-btn class="mr-1" small @click="$emit('cancel')" color="error">Cancel</v-btn>
        </div>
      </v-card-text>
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
    }
  },
  data() {
    return {
      code:null
    };
  },
};
</script>