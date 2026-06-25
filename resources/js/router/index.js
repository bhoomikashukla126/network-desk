import { createRouter, createWebHistory } from 'vue-router';
import AppLayout from '../layouts/AppLayout.vue';
import DashboardPage from '../pages/DashboardPage.vue';
import MapEditorPage from '../pages/MapEditorPage.vue';
import PointsIndex from '../pages/PointsIndex.vue';
import AccessControlPage from '../pages/AccessControlPage.vue';

const routes = [
    {
        path: '/',
        component: AppLayout,
        children: [
            { path: '', name: 'dashboard.index', component: DashboardPage },
            { path: 'map', name: 'map.index', component: MapEditorPage, meta: { fillViewport: true } },
            { path: 'points', name: 'points.index', component: PointsIndex },
            { path: 'access', name: 'access.index', component: AccessControlPage },
        ],
    },
];

export default createRouter({
    history: createWebHistory(),
    routes,
    scrollBehavior() {
        return { top: 0 };
    },
});
