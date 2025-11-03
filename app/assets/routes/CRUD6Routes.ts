export default [
    {
        path: '/crud6/:model',
        meta: {
            auth: {},
            // Don't set title here - let child components set it dynamically based on schema
        },
        children: [
            {
                path: '',
                name: 'crud6.list',
                meta: {
                    // Title will be set by PageList.vue based on schema
                    permission: {
                        slug: 'uri_crud6'
                    }
                },
                component: () => import('../views/PageList.vue')
            },
            {
                path: ':id',
                name: 'crud6.view',
                meta: {
                    // Title will be set by PageRow/PageMasterDetail based on schema and record data
                    description: 'CRUD6.INFO_PAGE',
                    permission: {
                        slug: 'uri_crud6'
                    }
                },
                component: () => import('../views/PageDynamic.vue')
            }
        ]
    }
]
