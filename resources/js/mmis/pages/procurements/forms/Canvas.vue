<template>
  <v-dialog
    v-model="show"
    width="1250"
    transition="dialog-bottom-transition"
    scrollable
    persistent
  >
    <v-card tile :loading="isfetching">
      <v-toolbar flat dark color="primary">
        <v-toolbar-title>Purchase request</v-toolbar-title>
        <v-spacer></v-spacer>
        <v-btn :disabled="show_canvas_form" icon dark @click="close()">
          <v-icon>mdi-close</v-icon>
        </v-btn>
      </v-toolbar>
      <v-card-text>
        <Field :payload="payload" />
        <ItemTable
          :items="payload.purchase_request_details"
          @addCanvas="showCanvasForm"
        />
      <div class="d-flex flex-row-reverse">
        <v-btn :disabled="isSubmitted" color="primary" @click="isapproved?checkStatus():confirmSubmit()"
          >{{isapproved?'Submit':'Submit canvas'}}</v-btn
        >
      </div>
      </v-card-text>
    </v-card>
    <CanvasForm
      :show="show_canvas_form"
      :selected_item="selected_item"
      :payload="payload"
      @close="closeForm"
    />
    <Confirmation
      @cancel="cancelConfirmation"
      @confirm="confirmAction"
      :show="isconfirmation"
    />
    <SnackBar
      class="class-snackbar"
      @close="isnotification = false"
      :data="notification"
      :show="isnotification"
    />
    <Remarks
      @cancel="isremarks = false"
      :show="isremarks"
      :payload="payload"
      @submit="approveCanvas"
    />
  </v-dialog>
</template>
<script>
import Field from "./includes/canvas/fields.vue";
import CanvasForm from "./includes/canvas/CanvasForm.vue";
import ItemTable from "./includes/canvas/ItemTable.vue";
import { apiGetPurchaseRequest, apiSubmitCanvas, apiApproveCanvas } from "@mmis/api/procurements.api";
export default {
  components: {
    Field,
    ItemTable,
    CanvasForm,
  },
  props: {
    show: {
      type: Boolean,
      required: true,
    },
    pr_id: {
      type: Number,
      required: true,
    },
    isapproved:{
      type: Boolean,
      required: true,
    }
  },
  data() {
    return {
      isfetching: true,
      show_canvas_form: false,
      isnotification: false,
      isconfirmation: false,
      isremarks: false,
      notification: {
        messages: [],
      },
      selected_item: {},
      payload: {},
    };
  },
  methods: {
    cancelConfirmation(){
      this.isconfirmation = false;
    },
    confirmAction(code){
      if (this.$store.getters.user.passcode != code || code == null) {
        this.showNotification("Incorrect passcode", "error")
        this.isnotification = true;
        return;
      }
      this.isconfirmation = false
      if(this.isapproved) return this.approveCanvas()
      this.submitCanvas()
    },
    checkStatus(){
      this.payload.purchase_request_details.map(details=>{
        if(!details.isapproved){
          this.isremarks = true
        }
      })
      if(!this.isremarks) this.confirmSubmit()
    },
    confirmSubmit(){
      this.isconfirmation = true
    },
    showNotification(message, color){
      this.notification.messages = [];
      this.notification.messages.push(message);
      this.notification.color = color;
    },
    async approveCanvas(remarks=null){
      let payload = this.payload.purchase_request_details.map(detail=>{
        return {item_id: detail.id, status: detail.isapproved, remarks: remarks}
      })
      console.log(payload, "test")
      let res = await apiApproveCanvas({items: payload})

      if(res.status == 200){
        this.isremarks = false
        this.isapproved = false
        
        this.$emit('submit')
      }
    },
    async submitCanvas(){
      let payload = this.payload.purchase_request_details.map(detail=>{
        if(detail.is_submitted == true){
          return detail.id
        }
      })
      let res = await apiSubmitCanvas({items: payload})
      if(res.status == 200){
        this.showNotification("Item successfully submitted", "success")
        this.isnotification = true;
      }
    },
    closeForm() {
      this.show_canvas_form = false;
      this.fetchPR();
    },
    showCanvasForm(item) {
      Object.assign(this.selected_item, item);
      setTimeout(() => {
        this.show_canvas_form = true;
      }, 50);
    },
    close() {
      this.$emit("close");
    },
    async fetchPR() {
      this.isfetching = true;
      let res = await apiGetPurchaseRequest(this.pr_id);

      if (res.status == 200) {
        this.payload = res.data;
        this.payload.purchase_request_details.map(detail=>{
          detail.isapproved = true;
          return detail
        })
        this.isfetching = false;
      }
    },
  },
  computed:{
    isSubmitted(){
      let submitted = true
      if(this.payload.purchase_request_details){
        if(this.payload.purchase_request_details.length > 0) {
          this.payload.purchase_request_details.map(details=>{
            if(details.is_submitted==true) submitted = false
          })
        }
      }
      return submitted
    }
  },
  watch: {
    pr_id: {
      handler(val) {
        if (val) {
          this.fetchPR();
        }
      },
      immediate: true,
    },
  },
};
</script>