<?php

namespace MarkusLehr\ClientGallerie\Infrastructure\Container;

use MarkusLehr\ClientGallerie\Infrastructure\Logging\LoggerRegistry;

/**
 * Service Container für Dependency Injection
 * Registriert und verwaltet alle Services des Plugins
 * 
 * @package MarkusLehr\ClientGallerie\Infrastructure\Container
 * @author Markus Lehr
 * @since 1.0.0
 */
class ServiceContainer 
{
    private array $services = [];
    private array $singletons = [];
    
    public function register(): void 
    {
        $logger = LoggerRegistry::getLogger();
        
        // Register core services
        $this->registerLoggingServices();
        $this->registerDatabaseServices();
        $this->registerDomainServices();
        $this->registerInfrastructureServices();
        $this->registerApplicationServices();
        
        $logger?->info('Service container registered', [
            'services_count' => count($this->services),
            'services' => array_keys($this->services)
        ]);
    }
    
    private function registerLoggingServices(): void 
    {
        // Logger ist bereits registriert, aber wir registrieren zusätzliche Services
        // Nur Services registrieren, die tatsächlich existieren
        
        // $this->singleton('log_viewer', function() {
        //     return new \MarkusLehr\ClientGallerie\Infrastructure\Logging\LogViewer();
        // });
        
        // $this->singleton('log_cleaner', function() {
        //     return new \MarkusLehr\ClientGallerie\Infrastructure\Logging\LogCleaner();
        // });
    }
    
    private function registerDatabaseServices(): void 
    {
        $this->singleton('database_installer', function() {
            return new \MarkusLehr\ClientGallerie\Infrastructure\Database\Installer();
        });
        
        // Implementierte Repositories
        $this->singleton('client_repository', function() {
            return new \MarkusLehr\ClientGallerie\Infrastructure\Database\Repository\ClientRepository();
        });
        
        $this->singleton('log_entry_repository', function() {
            return new \MarkusLehr\ClientGallerie\Infrastructure\Database\Repository\LogEntryRepository();
        });
        
        // Gallery Repository
        $this->singleton(\MarkusLehr\ClientGallerie\Domain\Gallery\Repository\GalleryRepositoryInterface::class, function() {
            global $wpdb;
            return new \MarkusLehr\ClientGallerie\Infrastructure\Database\Repository\GalleryRepository($wpdb);
        });
    }
    
    private function registerDomainServices(): void 
    {
        $this->singleton('security_manager', function() {
            return new \MarkusLehr\ClientGallerie\Domain\Security\Service\SecurityManager();
        });
        
        // TODO: Implementiere NotificationService
        // $this->singleton('notification_service', function() {
        //     return new \MarkusLehr\ClientGallerie\Domain\Notification\Service\NotificationService();
        // });
    }
    
    private function registerInfrastructureServices(): void 
    {
        $this->singleton('ajax_handler', function() {
            return new \MarkusLehr\ClientGallerie\Infrastructure\Http\AjaxHandler();
        });
        
        // TODO: Implementiere diese Services
        // $this->singleton('file_manager', function() {
        //     return new \MarkusLehr\ClientGallerie\Infrastructure\Storage\FileManager();
        // });
        
        // $this->singleton('cache_manager', function() {
        //     return new \MarkusLehr\ClientGallerie\Infrastructure\Cache\CacheManager();
        // });
    }
    
    private function registerApplicationServices(): void 
    {
        // Command Handlers
        $this->singleton(\MarkusLehr\ClientGallerie\Application\Handler\CreateGalleryHandler::class, function() {
            return new \MarkusLehr\ClientGallerie\Application\Handler\CreateGalleryHandler(
                $this->get(\MarkusLehr\ClientGallerie\Domain\Gallery\Repository\GalleryRepositoryInterface::class)
            );
        });

        $this->singleton(\MarkusLehr\ClientGallerie\Application\Handler\DeleteGalleryHandler::class, function() {
            return new \MarkusLehr\ClientGallerie\Application\Handler\DeleteGalleryHandler(
                $this->get(\MarkusLehr\ClientGallerie\Domain\Gallery\Repository\GalleryRepositoryInterface::class)
            );
        });

        $this->singleton(\MarkusLehr\ClientGallerie\Application\Handler\UpdateGalleryHandler::class, function() {
            return new \MarkusLehr\ClientGallerie\Application\Handler\UpdateGalleryHandler(
                $this->get(\MarkusLehr\ClientGallerie\Domain\Gallery\Repository\GalleryRepositoryInterface::class)
            );
        });

        $this->singleton(\MarkusLehr\ClientGallerie\Application\Handler\PublishGalleryHandler::class, function() {
            return new \MarkusLehr\ClientGallerie\Application\Handler\PublishGalleryHandler(
                $this->get(\MarkusLehr\ClientGallerie\Domain\Gallery\Repository\GalleryRepositoryInterface::class)
            );
        });

        // Query Handlers
        $this->singleton(\MarkusLehr\ClientGallerie\Application\Handler\GetGalleryQueryHandler::class, function() {
            return new \MarkusLehr\ClientGallerie\Application\Handler\GetGalleryQueryHandler(
                $this->get(\MarkusLehr\ClientGallerie\Domain\Gallery\Repository\GalleryRepositoryInterface::class)
            );
        });

        $this->singleton(\MarkusLehr\ClientGallerie\Application\Handler\ListGalleriesQueryHandler::class, function() {
            return new \MarkusLehr\ClientGallerie\Application\Handler\ListGalleriesQueryHandler(
                $this->get(\MarkusLehr\ClientGallerie\Domain\Gallery\Repository\GalleryRepositoryInterface::class)
            );
        });

        // Buses
        $this->singleton(\MarkusLehr\ClientGallerie\Application\Bus\CommandBusInterface::class, function() {
            return new \MarkusLehr\ClientGallerie\Infrastructure\Bus\SimpleCommandBus(
                $this->get(\MarkusLehr\ClientGallerie\Application\Handler\CreateGalleryHandler::class),
                $this->get(\MarkusLehr\ClientGallerie\Application\Handler\DeleteGalleryHandler::class),
                $this->get(\MarkusLehr\ClientGallerie\Application\Handler\UpdateGalleryHandler::class),
                $this->get(\MarkusLehr\ClientGallerie\Application\Handler\PublishGalleryHandler::class)
            );
        });

        $this->singleton(\MarkusLehr\ClientGallerie\Application\Bus\QueryBusInterface::class, function() {
            return new \MarkusLehr\ClientGallerie\Infrastructure\Bus\SimpleQueryBus(
                $this->get(\MarkusLehr\ClientGallerie\Application\Handler\GetGalleryQueryHandler::class),
                $this->get(\MarkusLehr\ClientGallerie\Application\Handler\ListGalleriesQueryHandler::class)
            );
        });

        // Admin Controller
        $this->singleton('admin_controller', function() {
            return new \MarkusLehr\ClientGallerie\Application\Controller\AdminController();
        });

        $this->singleton(\MarkusLehr\ClientGallerie\Infrastructure\Admin\GalleryAdminController::class, function() {
            return new \MarkusLehr\ClientGallerie\Infrastructure\Admin\GalleryAdminController(
                $this->get(\MarkusLehr\ClientGallerie\Application\Bus\CommandBusInterface::class),
                $this->get(\MarkusLehr\ClientGallerie\Application\Bus\QueryBusInterface::class)
            );
        });
        
        // TODO: Implementiere diese Controller
        // $this->singleton('frontend_controller', function() {
        //     return new \MarkusLehr\ClientGallerie\Application\Controller\FrontendController();
        // });
        
        // $this->singleton('api_controller', function() {
        //     return new \MarkusLehr\ClientGallerie\Application\Controller\ApiController();
        // });
    }
    
    public function singleton(string $name, callable $factory): void 
    {
        $this->services[$name] = $factory;
        
        LoggerRegistry::getLogger()?->debug("Service registered: $name");
    }
    
    public function get(string $name) 
    {
        if (!isset($this->services[$name])) {
            throw new \InvalidArgumentException("Service '$name' not found");
        }
        
        if (!isset($this->singletons[$name])) {
            $this->singletons[$name] = $this->services[$name]();
            
            LoggerRegistry::getLogger()?->debug("Service instantiated: $name");
        }
        
        return $this->singletons[$name];
    }
    
    public function has(string $name): bool 
    {
        return isset($this->services[$name]);
    }
    
    public function getRegisteredServices(): array 
    {
        return array_keys($this->services);
    }
}
