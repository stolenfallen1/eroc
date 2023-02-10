<script>
export default {
  methods: {
    _push(child, params = {}) {
      let link = this.$router.resolve({
        name: child,
        params: params,
      });

      let path = link.route.path;

      if (this.$route.path == path) return;

      this.$router.push({ path: path });
    },
    _getters(key) {
      return this.$store.getters[key];
    },
  },
};
</script>