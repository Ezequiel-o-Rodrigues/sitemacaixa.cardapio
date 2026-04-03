// modules/admin/admin.js

function openUserModal(user) {
    const modalEl = document.getElementById('userModal');
    const userId = document.getElementById('userId');
    const userNome = document.getElementById('userNome');
    const userEmail = document.getElementById('userEmail');
    const userSenha = document.getElementById('userSenha');
    const userPerfil = document.getElementById('userPerfil');
    const userAtivo = document.getElementById('userAtivo');

    if (!user) {
        // novo
        userId.value = '';
        userNome.value = '';
        userEmail.value = '';
        userSenha.value = '';
        userPerfil.value = 'usuario';
        userAtivo.checked = true;
    } else {
        userId.value = user.id || '';
        userNome.value = user.nome || '';
        userEmail.value = user.email || '';
        userSenha.value = '';
        userPerfil.value = user.perfil || 'usuario';
        userAtivo.checked = !!user.ativo;
    }

    const modal = new bootstrap.Modal(modalEl);
    modal.show();
}

document.addEventListener('DOMContentLoaded', function () {
    const btnNovo = document.getElementById('btn-novo-usuario');
    if (btnNovo) btnNovo.addEventListener('click', () => openUserModal(null));
});

function novaCategoria() {
    document.getElementById('categoriaModalTitle').textContent = 'Nova Categoria';
    document.getElementById('cat_nome').value = '';
    document.getElementById('cat_id').value = '';
}

function editarCategoria(id, nome) {
    document.getElementById('categoriaModalTitle').textContent = 'Editar Categoria';
    document.getElementById('cat_nome').value = nome;
    document.getElementById('cat_id').value = id;
}

async function salvarCategoria() {
    const nome = document.getElementById('cat_nome').value;
    const id = document.getElementById('cat_id').value;

    if (!nome) {
        alert('Nome é obrigatório');
        return;
    }

    try {
        const response = await fetch(PathConfig.api('salvar_categoria.php'), {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id, nome })
        });

        const data = await response.json();
        if (data.success) {
            location.reload();
        } else {
            alert(data.message);
        }
    } catch (e) {
        console.error(e);
        alert('Erro ao salvar categoria');
    }
}

async function deletarCategoria(id) {
    if (!confirm('Deseja realmente excluir esta categoria?')) return;

    try {
        const response = await fetch(PathConfig.api('deletar_categoria.php'), {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id })
        });

        const data = await response.json();
        if (data.success) {
            location.reload();
        } else {
            alert(data.message);
        }
    } catch (e) {
        console.error(e);
        alert('Erro ao excluir categoria');
    }
}

// ==========================================
// GERENCIAMENTO DE PRODUTOS
// ==========================================

function novoProduto() {
    document.getElementById('produtoModalTitle').textContent = 'Novo Produto';
    document.getElementById('produtoForm').reset();
    document.getElementById('prod_id').value = '';
    document.getElementById('prod_imagem_preview').innerHTML = '';
    document.getElementById('estoque_inicial_group').style.display = '';
    const modal = new bootstrap.Modal(document.getElementById('produtoModal'));
    modal.show();
}

function editarProduto(prod) {
    document.getElementById('produtoModalTitle').textContent = 'Editar Produto';
    document.getElementById('prod_id').value = prod.id;
    document.getElementById('prod_nome').value = prod.nome;
    document.getElementById('prod_categoria').value = prod.categoria_id;
    document.getElementById('prod_preco').value = prod.preco;
    document.getElementById('prod_estoque_minimo').value = prod.estoque_minimo;
    // Esconder estoque inicial na edição (não faz sentido redefinir)
    document.getElementById('estoque_inicial_group').style.display = 'none';

    const preview = document.getElementById('prod_imagem_preview');
    if (prod.imagem) {
        preview.innerHTML = '<img src="' + PathConfig.url('public/images/products/' + prod.imagem) + '" style="width:60px;height:60px;object-fit:cover;border-radius:4px;">';
    } else {
        preview.innerHTML = '';
    }

    const modal = new bootstrap.Modal(document.getElementById('produtoModal'));
    modal.show();
}

document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('produtoForm');
    if (form) {
        form.addEventListener('submit', async function(e) {
            e.preventDefault();
            await salvarProduto();
        });
    }
});

async function salvarProduto() {
    const id = document.getElementById('prod_id').value;
    const nome = document.getElementById('prod_nome').value;
    const categoria_id = document.getElementById('prod_categoria').value;
    const preco = document.getElementById('prod_preco').value;
    const estoque_minimo = document.getElementById('prod_estoque_minimo').value;
    const estoque_inicial = document.getElementById('prod_estoque_inicial').value;
    const imagemInput = document.getElementById('prod_imagem');

    if (!nome || !categoria_id || !preco) {
        alert('Preencha os campos obrigatórios.');
        return;
    }

    const formData = new FormData();
    if (id) formData.append('id', id);
    formData.append('nome', nome);
    formData.append('categoria_id', categoria_id);
    formData.append('preco', preco);
    formData.append('estoque_minimo', estoque_minimo);
    if (!id) formData.append('estoque_inicial', estoque_inicial);
    if (imagemInput.files[0]) formData.append('imagem', imagemInput.files[0]);

    try {
        const response = await fetch(PathConfig.api('salvar_produto.php'), {
            method: 'POST',
            body: formData
        });
        const data = await response.json();
        if (data.success) {
            location.reload();
        } else {
            alert(data.message);
        }
    } catch (e) {
        console.error(e);
        alert('Erro ao salvar produto');
    }
}

async function toggleProduto(produtoId, ativoAtual) {
    const novoStatus = ativoAtual ? 0 : 1;
    const acao = novoStatus ? 'ativar' : 'desativar';
    if (!confirm('Deseja ' + acao + ' este produto?')) return;

    try {
        const response = await fetch(PathConfig.api('toggle_produto.php'), {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ produto_id: produtoId, ativo: novoStatus })
        });
        const data = await response.json();
        if (data.success) {
            location.reload();
        } else {
            alert(data.message);
        }
    } catch (e) {
        console.error(e);
        alert('Erro ao alterar status do produto');
    }
}

async function deletarProduto(id, nome) {
    if (!confirm('Deseja excluir o produto "' + nome + '"? Esta ação não pode ser desfeita.')) return;

    try {
        const response = await fetch(PathConfig.api('deletar_produto.php'), {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id })
        });
        const data = await response.json();
        if (data.success) {
            location.reload();
        } else {
            alert(data.message);
        }
    } catch (e) {
        console.error(e);
        alert('Erro ao excluir produto');
    }
}

function filtrarProdutos() {
    const texto = document.getElementById('filtro-produto').value.toLowerCase();
    const categoriaId = document.getElementById('filtro-categoria').value;
    const rows = document.querySelectorAll('.produto-row');

    rows.forEach(row => {
        const nome = row.getAttribute('data-nome');
        const cat = row.getAttribute('data-categoria');
        const matchTexto = !texto || nome.includes(texto);
        const matchCategoria = !categoriaId || cat === categoriaId;
        row.style.display = (matchTexto && matchCategoria) ? '' : 'none';
    });
}
