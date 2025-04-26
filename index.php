<?php
require_once 'functions.php';

// Получаем все категории комплектующих
$categories = getCategories();
?>

<?php include 'components/header.php'; ?>

<div class="row">
    <div class="col-12 mb-4">
        <h1 class="display-5">Конфигуратор ПК</h1>
        <div class="d-flex">
            <div class="btn-group me-3">
                <a href="#" class="btn btn-primary active">
                    <i class="fas fa-cogs me-1"></i>Подберите сборку ПК
                </a>
                <a href="catalog.php" class="btn btn-outline-primary">
                    <i class="fas fa-list me-1"></i>Каталог конфигураций
                </a>
            </div>
            <a href="help.php" class="btn btn-link">
                <i class="fas fa-question-circle me-1"></i>Помощь по конфигуратору
            </a>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="configurator-components">
            <h2>Системный блок</h2>
            <p class="text-muted small">* Обязательные комплектующие</p>
            
            <form id="configurator-form">
                <?php foreach ($categories as $category): ?>
                    <div class="component-selection card mb-3">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <div>
                                <?php echo htmlspecialchars($category['name']); ?>
                                <?php if ($category['is_required']): ?>
                                    <span class="text-danger">*</span>
                                <?php endif; ?>
                            </div>
                            <div class="text-muted small">
                                <?php echo getCategoryItemsCount($category['id']); ?> товаров
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="selected-component" data-category-id="<?php echo $category['id']; ?>">
                                <p class="text-muted empty-selection">Не выбрано</p>
                            </div>
                            <div class="text-end">
                                <button type="button" class="btn btn-outline-primary btn-sm add-component" 
                                        data-category-id="<?php echo $category['id']; ?>"
                                        data-category-name="<?php echo htmlspecialchars($category['name']); ?>">
                                    <i class="fas fa-plus me-1"></i>Добавить
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
                
                <div class="d-grid gap-2 mt-4">
                    <button type="button" id="save-config" class="btn btn-success">
                        <i class="fas fa-save me-1"></i>Сохранить конфигурацию
                    </button>
                    <button type="submit" id="buy-config" class="btn btn-danger">
                        <i class="fas fa-shopping-cart me-1"></i>Купить с бесплатной сборкой
                    </button>
                    <button type="submit" id="buy-without-assembly" class="btn btn-outline-secondary">
                        <i class="fas fa-box me-1"></i>Купить без сборки
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <div class="col-lg-4">
        <div class="config-summary card sticky-top">
            <div class="card-header">
                <h3 class="h5 mb-0">Ваша конфигурация</h3>
            </div>
            <div class="card-body">
                <div class="pc-visualization mb-4">
                    <img src="assets/img/pc-case.png" alt="Корпус ПК" class="img-fluid">
                </div>
                
                <div class="mb-3">
                    <div class="d-flex justify-content-between">
                        <span>Гарантия</span>
                        <span>2 года</span>
                    </div>
                </div>
                
                <div class="mb-3">
                    <input type="text" class="form-control" id="config-name" placeholder="Название (необязательное поле)">
                </div>
                
                <div class="config-price mb-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h4>Итого:</h4>
                        <h4 class="total-price">0 ₽</h4>
                    </div>
                </div>
                
                <div class="d-grid gap-2">
                    <button type="button" id="save-config-sidebar" class="btn btn-primary">Сохранить</button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Модальное окно выбора комплектующих -->
<div class="modal fade" id="componentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Выбор компонента: <span id="component-category-name"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрыть"></button>
            </div>
            <div class="modal-body">
                <div class="input-group mb-3">
                    <input type="text" class="form-control" id="component-search" placeholder="Поиск по названию...">
                    <button class="btn btn-outline-secondary" type="button">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
                
                <div class="components-list">
                    <!-- Здесь будут отображаться компоненты из выбранной категории -->
                    <div class="text-center py-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Загрузка...</span>
                        </div>
                        <p class="mt-2">Загрузка комплектующих...</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Закрыть</button>
            </div>
        </div>
    </div>
</div>

<!-- Модальное окно сохранения конфигурации -->
<div class="modal fade" id="saveConfigModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Сохранение конфигурации</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрыть"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="save-config-name" class="form-label">Название конфигурации</label>
                    <input type="text" class="form-control" id="save-config-name" placeholder="Моя игровая сборка">
                </div>
                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" id="is-public-config">
                    <label class="form-check-label" for="is-public-config">
                        Доступно для всех
                    </label>
                    <div class="form-text">Если отмечено, ваша конфигурация будет доступна в общем каталоге</div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                <button type="button" class="btn btn-primary" id="confirm-save-config">Сохранить</button>
            </div>
        </div>
    </div>
</div>

<?php include 'components/footer.php'; ?>