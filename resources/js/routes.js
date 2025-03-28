import Home from './views/Home'
import Memory from './views/Memory'
import Account from './views/Account'
import NotFound from './views/NotFound'

export default [
    {
        path: '/home',
        name: 'home',
        component: Home,
    },
    {
        path: '/memory/:id',
        name: 'memory',
        component: Memory,
        props: true,

    },
    {
        path: '/account',
        name: 'account',
        component: Account
    },
    {
        path: '*',
        component: NotFound
    }
];
