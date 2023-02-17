<template>
  <v-dialog v-model="show" persistent width="300">
    <v-card elevation="1">
      <v-card-title>Confirmation</v-card-title>
      <v-card-text>
        <p class="ma-0 pa-0">Passcode</p>
        <v-text-field v-model="code" placeholder="Enter your passcode" solo dense>
        </v-text-field>
        <div class="d-flex flex-row-reverse">
          <v-btn small @click="$emit('confirm', code)" color="primary">Confirm</v-btn>
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
  },
  data() {
    return {
      code:null
    };
  },
};
</script>