//Создание констант
const priceElements = document.querySelectorAll('.price');
const quantityElements = document.querySelectorAll('.quantity');
const totalElements = document.querySelectorAll('.total');

let totalQuantity = 0;//переменная количество товара
let totalPrice = 0;//переменная цена

for (let i = 0; i < priceElements.length; i++) {
  const price = parseFloat(priceElements[i].textContent);
  const quantity = parseInt(quantityElements[i].textContent);
  const total = parseFloat(totalElements[i].textContent);
  
  totalQuantity += quantity;
  totalPrice += total;
}

document.getElementById('total-quantity').textContent = totalQuantity;//обнавление значений на странице
document.getElementById('total-price').textContent = totalPrice
