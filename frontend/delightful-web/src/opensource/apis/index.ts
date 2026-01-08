import { generateContactApi } from "./modules/contact"
import delightfulClient from "./clients/delightful"
import chatWebSocketClient from "./clients/chatWebSocket"
import { generateUserApi } from "./modules/user"
import { generateCommonApi } from "./modules/common"
import { generateAuthApi } from "./modules/auth"
import { generateBotApi } from "./modules/bot"
import { generateCalendarApi } from "./modules/calendar"
import { generateFavoritesApi } from "./modules/favorites"
import { generateFileApi } from "./modules/file"
import { generateFlowApi } from "./modules/flow"
import { generateKnowledgeApi } from "./modules/knowledge"
import { generateSearchApi } from "./modules/search"
import { generateChatApi } from "./modules/chat"

/** Reset Service */
export const UserApi = generateUserApi(delightfulClient)
export const CommonApi = generateCommonApi(delightfulClient)

/** Delightful Service */
export const AuthApi = generateAuthApi(delightfulClient)
export const ContactApi = generateContactApi(delightfulClient)
export const BotApi = generateBotApi(delightfulClient)
export const CalendarApi = generateCalendarApi(delightfulClient)
export const FavoritesApi = generateFavoritesApi(delightfulClient)
export const FileApi = generateFileApi(delightfulClient)
export const FlowApi = generateFlowApi(delightfulClient)
export const KnowledgeApi = generateKnowledgeApi(delightfulClient)
export const SearchApi = generateSearchApi(delightfulClient)
export const ChatApi = generateChatApi(delightfulClient, chatWebSocketClient)
