<?php
require_once __DIR__ . '/../caixa-seguro-7xy3q9kkle/config/database.php';

try {
    $database = new Database();
    $conn = $database->getConnection();

    // Fetch categories that have active products in stock
    $sql_categorias = "SELECT c.* FROM categorias c 
                       WHERE EXISTS (
                           SELECT 1 FROM produtos p 
                           WHERE p.categoria_id = c.id AND p.ativo = 1 AND p.estoque_atual > 0
                       ) ORDER BY c.nome";
    $stmt_cat = $conn->query($sql_categorias);
    $categorias = $stmt_cat->fetchAll(PDO::FETCH_ASSOC);

    // Fetch products
    $sql_produtos = "SELECT p.*, c.nome as categoria_nome FROM produtos p 
                     JOIN categorias c ON p.categoria_id = c.id 
                     WHERE p.ativo = 1 AND p.estoque_atual > 0 
                     ORDER BY c.nome, p.nome";
    $stmt_prod = $conn->query($sql_produtos);
    $produtos = $stmt_prod->fetchAll(PDO::FETCH_ASSOC);

    // Group products
    $menuData = [];
    foreach ($produtos as $p) {
        $cat_id = $p['categoria_id'];
        if (!isset($menuData[$cat_id])) {
            $menuData[$cat_id] = [];
        }
        $menuData[$cat_id][] = $p;
    }
} catch (Exception $e) {
    error_log("Database error in cardapio.php: " . $e->getMessage());
    $categorias = [];
    $menuData = [];
}

// Function to resolve image path matching the old structure
function resolveImagePath($imagem) {
    $default = 'images/menu/default.jpg';
    if (empty($imagem)) return $default;
    
    // Check various old dynamic paths just in case the name matches old system files
    $paths_to_check = [
        'images/menu/espetos/',
        'images/menu/porcoes/',
        'images/menu/bebidas/',
        'images/menu/cervejas/',
        'images/menu/opcoes_buffet/',
        'images/menu/'
    ];
    
    foreach ($paths_to_check as $dir) {
        if (file_exists($dir . $imagem)) {
            return $dir . htmlspecialchars($imagem);
        }
    }
    
    return $default;
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Espetinho do Júnior</title>
    <link rel="stylesheet" href="estilo.css">
    <link href="https://fonts.googleapis.com/css2?family=Palanquin+Dark:wght@400;500;600;700&family=Playfair+Display:wght@400;500;600;700&family=Roboto:wght@300;400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
    :root {
    --primary-color: #8B0000;
    --secondary-color: #1a1a1a;
    --accent-color: #d4af37;
    --text-color: #333;
    --bg-light: #f8f5f0;
    --bg-dark: #1a1a1a;
}

body {
    font-family: 'Roboto', sans-serif;
    color: var(--text-color);
    background-color: var(--bg-light);
    line-height: 1.5;
    margin: 0;
    padding: 0;
}

.container {
    width: 95%;
    max-width: 1400px;
    margin: 0 auto;
    padding: 0 10px;
}

/* Header mobile */
header {
    background: linear-gradient(to right, var(--bg-dark), var(--primary-color));
    color: white;
    padding: 15px 0;
    box-shadow: 0 2px 8px rgba(0,0,0,0.2);
}

header h1 {
    font-family: 'Playfair Display', serif;
    font-size: 1.5rem;
    text-align: center;
    margin-bottom: 5px;
}

.header-subtitle {
    text-align: center;
    font-size: 0.8rem;
    opacity: 0.9;
}

/* Seções do cardápio */
.menu-section {
    margin: 30px 0;
}

.section-title {
    color: var(--primary-color);
    font-family: 'Playfair Display', serif;
    border-bottom: 2px solid var(--accent-color);
    padding-bottom: 5px;
    margin-bottom: 20px;
    font-size: 1.4rem;
    text-align: center;
}

/* Grid de itens - 3 colunas para mobile */
.menu-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 8px;
}

.menu-item {
    background: white;
    border-radius: 5px;
    overflow: hidden;
    box-shadow: 0 2px 6px rgba(0,0,0,0.08);
    position: relative;
}

.menu-item:before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 3px;
    background: var(--accent-color);
}

.item-img-container {
    position: relative;
    overflow: hidden;
    height: 100px;
}

.item-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.item-special {
    position: absolute;
    bottom: 5px;
    right: 5px;
    background: var(--accent-color);
    color: var(--secondary-color);
    padding: 2px 6px;
    border-radius: 10px;
    font-size: 0.6rem;
    font-weight: bold;
}

.item-info {
    padding: 8px;
}

.item-name {
    color: var(--secondary-color);
    margin: 0;
    font-size: 0.8rem;
    font-weight: 600;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.item-price {
    color: var(--accent-color);
    font-weight: bold;
    font-size: 0.9rem;
    margin-top: 5px;
}

.item-price:before {
    content: 'R$';
    font-size: 0.7rem;
    margin-right: 2px;
}

/* Ajustes para descrições */
.item-desc {
    font-size: 0.7rem;
    color: #666;
    margin: 5px 0;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    text-overflow: ellipsis;
}

@media (max-width: 767px) {
    .item-desc {
        display: inline; 
    }
    
    .item-desc strong {
        display: inline;
    }
    
    .item-desc[style*="display"] {
        display: block !important;
    }

}

@media (max-width: 359px) {
    .menu-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    .item-img-container {
        height: 90px;
    }
}

@media (min-width: 768px) {
    .menu-grid {
        gap: 15px;
    }
    .item-img-container {
        height: 130px;
    }
    .item-name {
        font-size: 0.9rem;
    }
    .item-price {
        font-size: 1rem;
    }
}

@media (min-width: 992px) {
    .menu-grid {
        grid-template-columns: repeat(4, 1fr);
        gap: 20px;
    }
    .item-img-container {
        height: 150px;
    }
    .item-info {
        padding: 12px;
    }
    .item-name {
        font-size: 1rem;
    }
}

@media (min-width: 1200px) {
    .menu-grid {
        grid-template-columns: repeat(5, 1fr);
    }
    .item-img-container {
        height: 160px;
    }
}

footer {
    background: var(--bg-dark);
    color: white;
    padding: 25px 0;
    text-align: center;
}

.footer-content {
    max-width: 600px;
    margin: 0 auto;
    padding: 0 15px;
}

.footer-logo {
    font-family: 'Playfair Display', serif;
    font-size: 1.3rem;
    margin-bottom: 10px;
    color: var(--accent-color);
}

.footer-info {
    margin: 8px 0;
    font-size: 0.8rem;
}

.social-icons {
    margin-top: 15px;
}

.social-icon {
    color: var(--accent-color);
    margin: 0 8px;
    font-size: 1.1rem;
}

#deliveryBtn {
    background-color: var(--accent-color);
    color: var(--secondary-color);
    border: none;
    padding: 10px 15px;
    font-size: 0.9rem;
    border-radius: 20px;
    cursor: pointer;
    font-weight: bold;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 20px auto;
    width: 90%;
    max-width: 280px;
}

#deliveryModal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.8);
    z-index: 1000;
}

#deliveryModal > div {
    background-color: white;
    margin: 15% auto;
    padding: 15px;
    border-radius: 8px;
    width: 90%;
    max-width: 350px;
    position: relative;
}

#closeModal {
    position: absolute;
    right: 12px;
    top: 8px;
    font-size: 20px;
    cursor: pointer;
}

#deliveryModal a {
    display: block;
    padding: 10px;
    margin-bottom: 8px;
    border-radius: 5px;
    text-decoration: none;
    font-weight: bold;
    text-align: center;
    font-size: 0.9rem;
}

#backToTop {
    display: none;
    position: fixed;
    bottom: 15px;
    right: 15px;
    background-color: var(--accent-color);
    color: white;
    width: 36px;
    height: 36px;
    border-radius: 50%;
    text-align: center;
    line-height: 36px;
    font-size: 1rem;
    z-index: 99;
    box-shadow: 0 2px 5px rgba(0,0,0,0.3);
}

.menu-navegacao {
    position: sticky;
    top: 0;
    background: linear-gradient(to right, var(--bg-dark), var(--primary-color));
    z-index: 1000;
    padding: 10px 0;
    box-shadow: 0 4px 12px rgba(0,0,0,0.3);
}

.menu-navegacao ul {
    display: flex;
    justify-content: center;
    list-style: none;
    padding: 0;
    margin: 0;
    flex-wrap: wrap;
}

.menu-navegacao li {
    margin: 0 10px;
}

.menu-navegacao a {
    color: white;
    text-decoration: none;
    font-weight: 500;
    padding: 8px 15px;
    border-radius: 20px;
    transition: all 0.3s;
    font-size: 0.9rem;
}

.menu-navegacao a:hover,
.menu-navegacao a:focus {
    background-color: var(--accent-color);
    color: var(--secondary-color);
}

main.container {
    padding-top: 20px;
}

.menu-section {
    scroll-margin-top: 80px; 
}

@media (max-width: 768px) {
    .menu-navegacao ul {
        justify-content: space-around;
    }
    
    .menu-navegacao li {
        margin: 5px;
    }
    
    .menu-navegacao a {
        padding: 6px 10px;
        font-size: 0.8rem;
    }
    
    .menu-section {
        scroll-margin-top: 60px;
    }
}
</style>
</head>
<body>
    <header>
        <div class="container">
            <h1>ESPETINHO DO JÚNIOR</h1>
            <p class="header-subtitle">EXCELÊNCIA EM SERVIR DESDE 2005</p>
        </div>
    </header>
    
    <!-- Menu de navegação dinâmico -->
    <nav class="menu-navegacao">
        <ul>
            <?php foreach($categorias as $cat): ?>
            <li><a href="#cat-<?= $cat['id'] ?>"><?= htmlspecialchars($cat['nome']) ?></a></li>
            <?php endforeach; ?>
            <li><a href="#delivery">Delivery</a></li>
        </ul>
    </nav>

    <main class="container">
        
        <?php foreach($categorias as $cat): ?>
        <?php if (!empty($menuData[$cat['id']])): ?>
        <!-- Seção Dinâmica -->
        <section id="cat-<?= $cat['id'] ?>" class="menu-section">
            <h2 class="section-title"><?= htmlspecialchars($cat['nome']) ?></h2>
            <div class="menu-grid">
                <?php foreach($menuData[$cat['id']] as $item): ?>
                <div class="menu-item">
                    <div class="item-img-container">
                        <img src="<?= resolveImagePath($item['imagem']) ?>" 
                             alt="<?= htmlspecialchars($item['nome']) ?>" 
                             class="item-img"
                             onerror="this.onerror=null; this.src='images/menu/default.jpg'">
                        <?php if($item['estoque_atual'] <= 10): ?>
                            <div class="item-special" style="background:#e74c3c;">Últimas unidades!</div>
                        <?php else: ?>
                            <div class="item-special">Disponível</div>
                        <?php endif; ?>
                    </div>
                    <div class="item-info">
                        <h3 class="item-name"><?= htmlspecialchars($item['nome']) ?></h3>
                        <p class="item-price"><?= number_format($item['preco'], 2, ',', '.') ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>
        <?php endforeach; ?>

        <!-- Seção Delivery -->
        <section id="delivery" class="menu-section" style="text-align: center; margin-top: 60px;">
            <button id="deliveryBtn" style="background-color: var(--accent-color); color: var(--secondary-color); 
            border: none; padding: 15px 30px; font-size: 1.2rem; border-radius: 50px; 
            cursor: pointer; font-weight: bold; transition: all 0.3s; box-shadow: 0 4px 8px rgba(0,0,0,0.2);
            display: flex; align-items: center; margin: 0 auto;">
                <i class="fas fa-motorcycle" style="margin-right: 10px;"></i>
                Fazemos Delivery Também!
            </button>
            
            <div id="deliveryModal" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; 
                    width: 100%; height: 100%; background-color: rgba(0,0,0,0.8); overflow: auto;">
                <div style="background-color: white; margin: 10% auto; padding: 30px; 
                        border-radius: 10px; max-width: 500px; position: relative; text-align: center;">
                    <span id="closeModal" style="position: absolute; right: 20px; top: 10px; 
                            font-size: 28px; cursor: pointer;">&times;</span>
                    
                    <h2 style="color: var(--primary-color); font-family: 'Playfair Display', serif; 
                            margin-bottom: 30px;">Escolha sua plataforma</h2>
                    
                    <div style="display: flex; flex-direction: column; gap: 20px;">
                        <a href="https://www.ifood.com.br/delivery/morrinhos-go/espetinho-do-junior-jantinhas-vila-santos-dumont-i/5ef0eab0-bc60-4bd1-8ec5-69cdc4314be4" 
                        target="_blank" 
                        style="background-color: #ea1d2c; color: white; padding: 15px; 
                                border-radius: 8px; text-decoration: none; font-weight: bold;
                                display: flex; align-items: center; justify-content: center;
                                transition: transform 0.3s;">
                            <img src="https://t2.tudocdn.net/652297?w=646&h=284" 
                            style="width: 30px; margin-right: 10px;" alt="iFood">
                            Pedir pelo iFood
                        </a>
                        
                        <a href="https://wa.me/556492397675" 
                        target="_blank" 
                        style="background-color: #25D366; color: white; padding: 15px; 
                                border-radius: 8px; text-decoration: none; font-weight: bold;
                                display: flex; align-items: center; justify-content: center;
                                transition: transform 0.3s;">
                            <img src="https://upload.wikimedia.org/wikipedia/commons/6/6b/WhatsApp.svg" 
                            style="width: 30px; margin-right: 10px;" alt="WhatsApp">
                            Pedir pelo WhatsApp
                        </a>
                    </div>
                    
                    <p style="margin-top: 30px; color: #666; font-size: 0.9rem;">
                        Horário de Delivery: 18h às 23h
                    </p>
                </div>
            </div>
        </section>

        <a href="#" id="backToTop"><i class="fas fa-arrow-up"></i></a>
    </main>

    <footer>
        <div class="footer-content">
            <div class="footer-logo">CHURRASCARIA DO JÚNIOR</div>
            <p class="footer-info"><i class="fas fa-map-marker-alt"></i> Av. 101, 474-524, Morrinhos GO, 75654-252, Brazil.</p>
            <p class="footer-info"><i class="fas fa-phone"></i> (64) 99239-7675</p>
            <p class="footer-info"><i class="fas fa-envelope"></i> espetinhojunior2@gmail.com</p>
            
            <div class="social-icons">
                <a href="#" class="social-icon"><i class="fab fa-facebook-f"></i></a>
                <a href="#" class="social-icon"><i class="fab fa-instagram"></i></a>
            </div>
            
            <p class="footer-info" style="margin-top: 30px; font-size: 0.9rem;">
                &copy; <?= date('Y') ?> Churrascaria do Júnior. Todos os direitos reservados.
            </p>
            <p class="footer-info" style="margin-top: 30px; font-size: 0.9rem;">
                &copy; <?= date('Y') ?> Site desenvolvido por: 
                <a href="https://www.instagram.com/ezequiel.o.rod?igsh=MTVvcGd2YXN2cDY4YQ%3D%3D" 
                target="_blank" 
                style="color: var(--accent-color); text-decoration: none; font-weight: 600;">
                Ezequiel Oliveira
                </a>
            </p>
        </div>
    </footer>

    <script>
        // Botão Voltar ao Topo
        window.addEventListener('scroll', function() {
            var backToTop = document.getElementById('backToTop');
            if (window.pageYOffset > 300) {
                backToTop.style.display = 'block';
            } else {
                backToTop.style.display = 'none';
            }
        });
        
        document.getElementById('backToTop').addEventListener('click', function(e) {
            e.preventDefault();
            window.scrollTo({top: 0, behavior: 'smooth'});
        });

        // Script para controlar o modal
        const deliveryBtn = document.getElementById('deliveryBtn');
        const deliveryModal = document.getElementById('deliveryModal');
        const closeModal = document.getElementById('closeModal');
        
        deliveryBtn.addEventListener('click', function() {
            deliveryModal.style.display = 'block';
            document.body.style.overflow = 'hidden';
        });
        
        closeModal.addEventListener('click', function() {
            deliveryModal.style.display = 'none';
            document.body.style.overflow = 'auto';
        });
        
        window.addEventListener('click', function(event) {
            if (event.target == deliveryModal) {
                deliveryModal.style.display = 'none';
                document.body.style.overflow = 'auto';
            }
        });
    </script>
</body>
</html>