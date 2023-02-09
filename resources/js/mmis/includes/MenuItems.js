export default [
  {
    name: "Procurement Transaction",
    icon: "mdi-folder",
    children: [
      {
        name: "Purchase request",
        slugs: "purchase-request",
        route: "purchase-request",
        alias: 'purchase-request',
        icon: "mdi-note-multiple"
      },
      {
        name: "Request for quotation",
        slugs: "request-quotation",
        route: "request-quotation",
        alias: 'request-quotation',
        icon: "mdi-note-multiple"
      },
      {
        name: "Canvas",
        slugs: "canvas",
        route: "canvas",
        alias: 'canvas',
        icon: "mdi-note-multiple"
      },
      {
        name: "Purchase order",
        slugs: "purchase-order",
        route: "purchase-order",
        alias: 'purchase-order',
        icon: "mdi-note-multiple"
      },
    ]
  },
  {
    name: "Audit Transactions",
    icon: "mdi-note-multiple",
    children: [
      {
        name: "Purchase request",
        slugs: "purchase-request",
        route: "purchase-request",
        alias: 'purchase-request',
        icon: "mdi-note-multiple"
      },
      {
        name: "Purchase orders",
        slugs: "purchase-orders",
        route: "purchase-orders",
        alias: 'purchase-orders',
        icon: "mdi-note-multiple"
      },
      {
        name: "Audit",
        slugs: "audit",
        route: "audit",
        alias: 'audit',
        icon: "mdi-note-multiple"
      },
    ]
  },
  {
    name: "Item Management",
    icon: "mdi-cube",
    children: [
      {
        name: "Item and Services",
        slugs: "item-services",
        route: "item-services",
        alias: 'item-services',
        icon: "mdi-note-multiple"
      },
      {
        name: "Item Master By Location",
        slugs: "item-master-location",
        route: "item-master-location",
        alias: 'item-master-location',
        icon: "mdi-note-multiple"
      },
      {
        name: "Beginning Inventory",
        slugs: "beginning-inventory",
        route: "beginning-inventory",
        alias: 'beginning-inventory',
        icon: "mdi-note-multiple"
      },
    ]
  },
  {
    name: "Inventory Management",
    icon: "mdi-cube",
    children: [
      {
        name: "Receiving Entries",
        slugs: "receiving-entries",
        route: "receiving-entries",
        alias: 'receiving-entries',
        icon: "mdi-note-multiple"
      },
      {
        name: "Stock Transfer",
        slugs: "stock-transfer",
        route: "stock-transfer",
        alias: 'stock-transfer',
        icon: "mdi-note-multiple"
      },
      {
        name: "Supply Requisition",
        slugs: "supply-requisition",
        route: "supply-requisition",
        alias: 'supply-requisition',
        icon: "mdi-note-multiple"
      },
      {
        name: "Stock Withdrawal",
        slugs: "stock-withdrawal",
        route: "stock-withdrawal",
        alias: 'stock-withdrawal',
        icon: "mdi-note-multiple"
      },
    ]
  },
  {
    name: "Location / Warehouse",
    icon: "mdi-domain",
    children: [
      {
        name: "Departments",
        slugs: "Departments",
        route: "Departments",
        alias: 'Departments',
        icon: "mdi-tag-multiple"
      },
      {
        name: "Department Section",
        slugs: "department-section",
        route: "department-section",
        alias: 'department-section',
        icon: "mdi-note-multiple"
      },
      {
        name: "Location",
        slugs: "location",
        route: "location",
        alias: 'location',
        icon: "mdi-note-multiple"
      },
    ]
  },
  // {
  //   name: "Build File",
  //   icon: "mdi-folder",
  //   children: [
  //     {
  //       name: "Inventory Group",
  //       slugs: "inventory-group",
  //       route: "inventory-group",
  //       alias: 'inventory-group',
  //       icon: "mdi-note-multiple"
  //     },
  //     {
  //       name: "Item Category",
  //       slugs: "item-category",
  //       route: "item-category",
  //       alias: 'item-category',
  //       icon: "mdi-note-multiple"
  //     },
  //     {
  //       name: "Classification",
  //       slugs: "classification",
  //       route: "classification",
  //       alias: 'classification',
  //       icon: "mdi-note-multiple"
  //     },
  //     {
  //       name: "Unit",
  //       slugs: "unit",
  //       route: "unit",
  //       alias: 'unit',
  //       icon: "mdi-note-multiple"
  //     },
  //     {
  //       name: "Vendor",
  //       slugs: "vendor",
  //       route: "vendor",
  //       alias: 'vendor',
  //       icon: "mdi-note-multiple"
  //     },
  //     {
  //       name: "Terms",
  //       slugs: "terms",
  //       route: "terms",
  //       alias: 'terms',
  //       icon: "mdi-note-multiple"
  //     },
  //   ]
  // },
  {
    name: "Mailbox",
    icon: "mdi-email",
    children: [
      {
        name: "Inbox",
        slugs: "inbox",
        route: "inbox",
        alias: 'inbox',
        icon: "mdi-inbox"
      },
    ]
  },
  {
    name: "Manage Supplier",
    icon: "mdi-domain",
    children: [
      {
        name: "Suppliers",
        slugs: "suppliers",
        route: "suppliers",
        alias: 'suppliers',
        icon: "mdi-note-multiple"
      },
    ]
  },
]