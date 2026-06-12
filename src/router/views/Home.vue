<template>
	<div class="home-container">
		<!-- 顶部导航栏 Header -->
		<header class="header">
			<!-- 左上角 Logo -->
			<div class="logo">
				<span>v图床</span>
			</div>

			<!-- 右上角 仅保留上传按钮 -->
			<div class="header-right">
				<button class="upload-btn" @click="openDialog">
					<svg viewBox="0 0 24 24" stroke="white" stroke-width="2" stroke-linecap="round" fill="none">
						<path d="M12 5v14M5 12h14"/>
					</svg>
					上传图片
				</button>
			</div>
		</header>

		<!-- 主内容区 Main -->
		<main class="main">
			<!-- 图片展示列表 -->
			<div class="image-list" v-if="imageList.length">
				<div class="image-item" v-for="(item, index) in imageList" :key="item.id">
					<img :src="item.url" alt="预览图" @dragstart.prevent />
					<button class="link-btn" @click="openLinkDialog(item)">获取链接</button>
				</div>
			</div>

			<!-- 空状态 -->
			<div class="empty-tip" v-else>
				<svg viewBox="0 0 24 24" stroke="#666" stroke-width="2" stroke-linecap="round" fill="none">
					<rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
					<circle cx="8.5" cy="8.5" r="1.5"/>
					<path d="M21 15l-5-5L5 21"/>
				</svg>
				<p>暂无图片，点击右上角上传按钮开始上传</p>
			</div>
		</main>

		<!-- 底部 Footer -->
		<footer class="footer">
			<p>© 2026 v图床 - 简约图片托管平台</p>
		</footer>

		<!-- 上传弹窗 Dialog -->
		<div class="dialog-mask" v-show="showDialog" @click="closeDialog">
			<div class="dialog-content" @click.stop>
				<div class="dialog-header">
					<h3>上传图片</h3>
					<button class="close-btn" @click="closeDialog">
						<svg viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" fill="none">
							<line x1="18" y1="6" x2="6" y2="18"></line>
							<line x1="6" y1="6" x2="18" y2="18"></line>
						</svg>
					</button>
				</div>
				<div class="dialog-body">
					<!-- 上传区域：点击+拖拽 -->
					<div 
						class="upload-area" 
						@click="selectFile"
						@dragover.prevent
						@dragleave.prevent
						@drop.prevent="handleDrop"
					>
						<svg viewBox="0 0 24 24" stroke="#666" stroke-width="2" stroke-linecap="round" fill="none">
							<path d="M21 15v4a2 20 0 0 1-2 2H5a2 20 0 0 1-2-2v-4"/>
							<polyline points="17 8 12 3 7 8"/>
							<line x1="12" y1="12" x2="12" y2="19"/>
						</svg>
						<p>点击或拖拽图片到此处上传</p>
						<p class="tip">支持 JPG/PNG/WebP 格式</p>
					</div>

					<!-- 预览区域：图片放大 + 垂直居中对齐 -->
					<div class="preview-box" v-if="previewImage">
						<img :src="previewImage" alt="预览" class="preview-img" @dragstart.prevent />
						<button class="start-upload" @click="uploadImage" :disabled="loading">
							<span v-if="!loading">确认上传</span>
							<span v-else>上传中...</span>
						</button>
					</div>

					<!-- 隐藏的文件选择框 -->
					<input 
						ref="fileInput" 
						type="file" 
						accept="image/*" 
						@change="handleFileChange" 
						hidden
					/>
				</div>
			</div>
		</div>

		<!-- 图片链接获取弹窗 -->
		<div class="dialog-mask" v-show="showLinkDialog" @click="closeLinkDialog">
			<div class="dialog-content link-dialog" @click.stop>
				<div class="dialog-header">
					<h3>获取图片链接</h3>
					<button class="close-btn" @click="closeLinkDialog">
						<svg viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" fill="none">
							<line x1="18" y1="6" x2="6" y2="18"></line>
							<line x1="6" y1="6" x2="18" y2="18"></line>
						</svg>
					</button>
				</div>
				<div class="dialog-body">
					<!-- 图片预览 -->
					<div class="link-preview">
						<img :src="currentImage?.url" alt="预览" @dragstart.prevent />
					</div>

					<!-- 三种格式区域 -->
					<div class="link-item">
						<label>直接图片链接</label>
						<div class="link-input-wrap">
							<input type="text" readonly :value="currentImage?.url" />
							<button class="copy-btn" @click="copyLink(currentImage?.url)">复制</button>
						</div>
					</div>

					<div class="link-item">
						<label>Markdown 引用格式</label>
						<div class="link-input-wrap">
							<input type="text" readonly :value="`![${currentImage?.name}](${currentImage?.url})`" />
							<button class="copy-btn" @click="copyLink(`![${currentImage?.name}](${currentImage?.url})`)">复制</button>
						</div>
					</div>

					<div class="link-item">
						<label>HTML 插入格式</label>
						<div class="link-input-wrap">
							<input type="text" readonly :value="`<img src='${currentImage?.url}' alt='${currentImage?.name}' />`" />
							<button class="copy-btn" @click="copyLink(`<img src='${currentImage?.url}' alt='${currentImage?.name}' />`)">复制</button>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import { VMessage } from '../../component/Message'

// 上传弹窗控制
const showDialog = ref(false)
// 文件上传相关
const fileInput = ref(null)
const selectedFile = ref(null)
const previewImage = ref('')
const loading = ref(false)
// 图片列表
const imageList = ref([])
// 链接弹窗
const showLinkDialog = ref(false)
const currentImage = ref(null)

const router = useRouter()

// 无限滚动分页参数
const page = ref(1)           // 当前页码
const limit = ref(50)         // 每页加载条数
const hasMore = ref(true)     // 是否还有更多数据
const loadingMore = ref(false)// 滚动加载锁，防止重复请求

// 获取图片列表（分页加载、数据向后追加）
const getImageList = async () => {
  const token = localStorage.getItem('token')
  if (!token) {
    VMessage("error", '请先登录！')
    router.replace("/login")
    return
  }

  // 无更多数据 / 正在加载 → 直接终止，不再请求
  if (!hasMore.value || loadingMore.value) return
  loadingMore.value = true

  try {
    const res = await fetch(`/api/img_list?page=${page.value}&limit=${limit.value}`, {
      method: 'GET',
      headers: {
        'token': token
      }
    })
    const result = await res.json()

    if (result.code === 200) {
      const list = result.data.list || []
      // 第一页覆盖，后续页面追加数据
      if (page.value === 1) {
        imageList.value = list
      } else {
        imageList.value = [...imageList.value, ...list]
      }

      // 当前页数据不足 limit，判定为无更多内容，关闭加载
      if (list.length < limit.value) {
        hasMore.value = false
      } else {
        page.value++
      }
    } else {
      VMessage("error", result.msg || '获取图片列表失败')
    }
  } catch (error) {
    VMessage("error", '获取图片列表失败，请检查网络')
    console.error('获取列表错误：', error)
  } finally {
    loadingMore.value = false
  }
}

// 页面滚动监听：触底加载下一页
const handleScroll = () => {
  // 距离底部 100px 触发加载阈值
  const scrollTop = document.documentElement.scrollTop || document.body.scrollTop
  const clientHeight = document.documentElement.clientHeight
  const scrollHeight = document.documentElement.scrollHeight

  if (scrollTop + clientHeight >= scrollHeight - 100) {
    getImageList()
  }
}

// 生命周期
onMounted(() => {
  getImageList()
  window.addEventListener('scroll', handleScroll)
})

onUnmounted(() => {
  // 组件销毁，移除滚动监听，防止内存泄漏
  window.removeEventListener('scroll', handleScroll)
})

// 打开上传弹窗
const openDialog = () => {
	showDialog.value = true
	selectedFile.value = null
	previewImage.value = ''
}

// 关闭上传弹窗
const closeDialog = () => {
	showDialog.value = false
	loading.value = false
}

// 点击选择文件
const selectFile = () => {
	fileInput.value.click()
}

// 处理文件选择
const handleFileChange = (e) => {
	const file = e.target.files[0]
	if (!file) return
	if (!file.type.startsWith('image/')) {
		VMessage("error", '请选择图片文件！')
		return
	}
	selectedFile.value = file
	previewImage.value = URL.createObjectURL(file)
}

// 拖拽上传
const handleDrop = (e) => {
	const file = e.dataTransfer.files[0]
	if (!file || !file.type.startsWith('image/')) {
		VMessage("error", '请拖拽图片文件！')
		return
	}
	selectedFile.value = file
	previewImage.value = URL.createObjectURL(file)
}

// 上传图片
const uploadImage = async () => {
	if (!selectedFile.value) {
		VMessage("error", '请选择图片！')
		return
	}
	const token = localStorage.getItem('token')
	if (!token) {
		VMessage("error", '请先登录！')
		router.replace('/login')
		return
	}

	loading.value = true
	const formData = new FormData()
	formData.append('file', selectedFile.value)

	try {
		const res = await fetch('/api/img_upload', {
			method: 'POST',
			headers: { 'token': token },
			body: formData
		})
		const result = await res.json()

		if (result.code === 200) {
			VMessage("success", '上传成功！')
			closeDialog()
			// 上传完成重置分页状态，重新从第一页加载
			page.value = 1
			hasMore.value = true
			getImageList()
		} else {
			VMessage("error", result.msg || '上传失败')
		}
	} catch (error) {
		VMessage("error", '上传失败，请检查网络或联系管理员！')
		console.error('上传接口错误：', error)
	} finally {
		loading.value = false
	}
}

// 打开链接弹窗
const openLinkDialog = (item) => {
	currentImage.value = item
	showLinkDialog.value = true
}

// 关闭链接弹窗
const closeLinkDialog = () => {
	showLinkDialog.value = false
	currentImage.value = null
}

// 复制链接
const copyLink = async (text) => {
	if (!text) return
	try {
		await navigator.clipboard.writeText(text)
		VMessage("success", '复制成功！')
	} catch (err) {
		console.error(err)
		VMessage("error", '复制失败！')
	}
}
</script>

<style lang="scss" scoped>
@use "../../scss/home.scss";
</style>