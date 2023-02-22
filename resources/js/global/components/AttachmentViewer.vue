<template>
  <v-dialog
    v-model="isshow"
    transition="dialog-bottom-transition"
    fullscreen
    hide-overlay
    scrollable
  >
    <v-card height="100vh">
      <v-card-title>
        View Attachment
        <v-spacer></v-spacer>
        <v-btn small text fab icon @click="$emit('close')"><v-icon>mdi-close</v-icon></v-btn>
      </v-card-title>
      <v-card-text>
        <!-- v-for="(file, index) in src_files" -->
        <iframe
          class="class-iframe"
          :src="src_files[index]"
          :key="index"
          frameborder="0"
        ></iframe>
      </v-card-text>
      <div class="d-flex justify-center ma-2">
        <v-btn :disabled="index==0" @click="index=index-1">Prev</v-btn>
        <v-btn :disabled="index == (files.length-1)" class="ml-2" @click="index=index+1">Next</v-btn>
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
      index:0,
    };
  },
  watch: {
    files: {
      handler(val) {
        if(val.length){
          console.log(val,"value")
          val.forEach(file => {
            if(file.filepath){
              this.src_files.push(file.filepath);
            }else{
              this.src_files.push(URL.createObjectURL(file));
            }
            console.log(file, "update file")
            // var reader = new FileReader();
            // reader.onload = function () {
            //   console.log(reader);
            // };
            
            // let url = reader.readAsDataURL(file)
            // console.log(file, "files")
            // console.log(, "urls")
          });
        }
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
<style lang="scss" scope>
.class-iframe{
  min-height: 100%;
  min-width: 100%;
}
</style>