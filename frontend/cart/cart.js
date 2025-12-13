const API = "../../backend/cart";

let checkoutIds = {};

document.addEventListener("DOMContentLoaded", () => {
  loadCart();
});

function loadCart() {
  fetch(`${API}/listCart.php`)
    .then(res => res.json())
    .then(cart => {
      renderCart(cart);
      renderCheckout(cart);
    });
}

function renderCart(cart) {
  const container = document.getElementById("cart-list");
  container.innerHTML = "";

  if (cart.length === 0) {
    container.innerHTML = `<p class="empty-cart">Your cart is empty.</p>`;
    return;
  }

  cart.forEach(item => {
    const total = item.price * item.quantity;
    const checked = checkoutIds[item.cart_id] ? "checked" : "";

    container.innerHTML += `
      <div class="cart-card">

        <div class="cart-select">
          <input type="checkbox" class="cart-select-checkbox"
            ${checked}
            onchange="toggleCheckout(${item.cart_id}, this.checked)">
        </div>

        <img
          src="../../assets/products/${item.image}"
          class="cart-img"
          alt="${item.name}"
        >

        <div class="cart-info">
          <div class="cart-info-header">
            <h3 class="cart-product-name">${item.name}</h3>
          </div>

          <div class="cart-size">
            Size: <span>${item.size}</span>
          </div>

          <div class="cart-info-bottom">
            <div class="qty-form">
              <button class="qty-btn" onclick="updateQty(${item.cart_id}, 'minus')">−</button>
              <div class="qty-number">${item.quantity}</div>
              <button class="qty-btn" onclick="updateQty(${item.cart_id}, 'plus')">+</button>
            </div>

            <div class="cart-price">
              Rp ${item.price.toLocaleString("id-ID")}
            </div>
          </div>
        </div>

        <div class="cart-summary">
          <div class="cart-summary-label">Total</div>
          <div class="cart-summary-value">
            Rp ${total.toLocaleString("id-ID")}
            <button class="delete-icon-btn summary-delete"
              onclick="deleteItem(${item.cart_id})">
              🗑
            </button>
          </div>
        </div>

      </div>
    `;
  });
}

function toggleCheckout(id, checked) {
  if (checked) checkoutIds[id] = true;
  else delete checkoutIds[id];
  loadCart();
}

function renderCheckout(cart) {
  const list = document.getElementById("checkout-list");
  const totalBox = document.getElementById("checkout-total");

  list.innerHTML = "";
  let total = 0;

  cart.forEach(item => {
    if (!checkoutIds[item.cart_id]) return;
    const line = item.price * item.quantity;
    total += line;

    list.innerHTML += `
      <div class="summary-item">
        <span>${item.name} x${item.quantity}</span>
        <span>Rp ${line.toLocaleString("id-ID")}</span>
      </div>
    `;
  });

  if (total === 0) {
    list.innerHTML = `<p style="color:#888;">No products have been selected yet</p>`;
  }

  totalBox.innerHTML = `Total : Rp ${total.toLocaleString("id-ID")}`;
}

function updateQty(id, action) {
  fetch(`${API}/update.php`, {
    method: "POST",
    headers: { "Content-Type": "application/x-www-form-urlencoded" },
    body: `cart_id=${id}&action=${action}`
  }).then(loadCart);
}

function deleteItem(id) {
  fetch(`${API}/delete.php?id=${id}`)
    .then(loadCart);
}

function cancelCheckout() {
  checkoutIds = {};
  loadCart();
}

function checkout() {
  if (Object.keys(checkoutIds).length === 0) {
    alert("Pilih item dulu sebelum checkout!");
    return;
  }
  window.location.href = "../../order/checkout.html";
}


