<script>
export default {
  methods:{
    hasActions(setting){
      if(setting.param_tab > 5) {
        return true
      }else{
        return false
      }
    },
  }
}
</script>