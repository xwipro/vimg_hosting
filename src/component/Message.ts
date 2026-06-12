import type { App } from 'vue'

// 定义类型
type MessageType = 'success' | 'error'

// 核心函数
const VMessage = (type: MessageType, text: string) => {
  const wrapper = document.createElement('div')
  wrapper.className = 'message-wrapper'

  const box = document.createElement('div')
  box.className = `message-box message-${type}`

  const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg')
  svg.setAttribute('viewBox', '0 0 24 24')
  svg.setAttribute('fill', 'none')
  svg.setAttribute('stroke-width', '2')
  svg.setAttribute('stroke-linecap', 'round')

  svg.innerHTML = type === 'success'
    ? '<path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>'
    : '<circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/>'

  const textDom = document.createElement('div')
  textDom.className = 'message-text'
  textDom.textContent = text

  box.appendChild(svg)
  box.appendChild(textDom)
  wrapper.appendChild(box)
  document.body.appendChild(wrapper)

  setTimeout(() => {
    box.style.animation = 'messageFadeOut 0.3s ease forwards'
    setTimeout(() => document.body.removeChild(wrapper), 300)
  }, 3000)
}

// 安装为 Vue 插件
export default {
  install(app: App) {
    // 全局挂载：任意组件直接用 $VMessage
    app.config.globalProperties.$VMessage = VMessage
  }
}

// 导出供单独使用
export { VMessage }