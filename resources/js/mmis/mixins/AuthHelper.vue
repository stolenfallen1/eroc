<script>
export default {
  methods:{
    can(val){
      return this.$store.getters.user_permissions.some(permission => permission.key == val)
    },
    
  }
}
</script>