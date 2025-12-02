export default [
    {
        path: '/crud6/:model',
        meta: {
            auth: {},
            // Note: title is intentionally NOT set here to prevent breadcrumb issues
            // with the {{model}} placeholder. The Vue components (PageList.vue, PageRow.vue)
            // dynamically set page.title based on the schema's title field, which
            // properly displays the translated model name in breadcrumbs.
            description: 'CRUD6.PAGE_DESCRIPTION'
        },
        children: [
            {
                path: '',
                name: 'crud6.list',
                meta: {
                    permission: {
                        slug: 'uri_crud6'
                    }
                    // Note: title is set dynamically by PageList.vue from schema
                },
                component: () => import('../views/PageList.vue')
            },
            {
                path: ':id',
                name: 'crud6.view',
                meta: {
                    description: 'CRUD6.INFO_PAGE',
                    permission: {
                        slug: 'uri_crud6'
                    }
                    // Note: title is set dynamically by PageRow.vue from schema
                },
                component: () => import('../views/PageDynamic.vue')
            }
        ]
    }
]
