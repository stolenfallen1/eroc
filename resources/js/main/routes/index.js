const mmis = {
    path: `/mmis`,
    name: `mmis`,
    beforeEnter(to, from, next) {
      window.location.href = "mmis/procurements/purchase-request";
    }
    // component: () => import("../pages/sample/sample.vue")
};

const component = [ mmis ];
export default component;