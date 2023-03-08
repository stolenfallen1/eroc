const item_management = {
    path: `/mmis/item_management`,
    name: `item-management`,
    // redirect: {
    //   name: 'purchase-request'
    // }
    component: () =>
        import ("../pages/item_management/item_services/index.vue")
};

const item_and_services = {
    path: `/mmis/item_management/item_services`,
    name: `item-services`,
    component: () =>
        import ("../pages/item_management/item_services/index.vue")
};

const component = [item_management, item_and_services];
export default component;