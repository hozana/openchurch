<?php

declare(strict_types=1);

namespace App\Admin\Infrastructure\EasyAdmin\Controller;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Override;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
#[AdminDashboard(routePath: '/admin', routeName: 'admin')]
final class DashboardController extends AbstractDashboardController
{
    #[Override]
    public function index(): Response
    {
        return $this->redirectToRoute('admin_parish_index');
    }

    #[Override]
    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('OpenChurch')
        ;
    }

    #[Override]
    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkTo(ParishController::class, 'Paroisses', 'fa fa-church')->setAction('index');
    }
}
