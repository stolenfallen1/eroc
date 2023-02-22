<template>
  <v-dialog
    v-model="isshow"
    transition="dialog-bottom-transition"
    max-width="900"
  >
    <v-card>
      <v-card-text>
        <iframe
          v-for="(file, index) in src_files"
          :src="file"
          :key="index"
          frameborder="0"
        ></iframe>
      </v-card-text>
      <div class="d-flex justify-center">
        <v-btn>Prev</v-btn>
        <v-btn>Next</v-btn>
      </div>
    </v-card>
  </v-dialog>
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
    show: {
      type: Boolean,
      default: () => {
        return false;
      },
    },
  },
  data() {
    return {
      src_files: [],
      isshow: false,
    };
  },
  watch: {
    files: {
      handler(val) {
        console.log(val,"value")
        val.forEach(file => {
          var reader = new FileReader();
          let url = reader.readAsDataURL(file)
          console.log(file, "files")
          console.log(url, "urls")
          this.src_files.push();
        });
      },
    },
    show: {
      handler(val) {
        this.isshow = val;
      },
    },
  },
};
</script>