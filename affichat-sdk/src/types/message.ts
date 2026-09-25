export interface SendTextParams {
  sessionId: string;
  to: string;
  text: string;
  is_group?: boolean;
}

export interface SendImageParams {
  sessionId: string;
  to: string;
  imageUrl: string;
  image_url?: string;
  caption?: string;
  is_group?: boolean;
}

export interface SendDocumentParams {
  sessionId: string;
  to: string;
  documentUrl: string;
  document_url?: string;
  filename?: string;
  fileName?: string;
  document_name?: string;
  caption?: string;
  is_group?: boolean;
}

export interface SendVideoParams {
  sessionId: string;
  to: string;
  videoUrl: string;
  video_url?: string;
  caption?: string;
  is_group?: boolean;
}

export interface SendLocationParams {
  sessionId: string;
  to: string;
  latitude: number;
  longitude: number;
  name?: string;
  title?: string;
  address?: string;
  is_group?: boolean;
}

export interface SendContactParams {
  sessionId: string;
  to: string;
  contactName: string;
  contactNumber: string;
  name?: string;
  phone?: string;
  phoneNumber?: string;
  is_group?: boolean;
}

export interface SendStickerParams {
  sessionId: string;
  to: string;
  stickerUrl: string;
  media?: string;
  is_group?: boolean;
}

export interface SendPollParams {
  sessionId: string;
  to: string;
  question: string;
  options: string[];
  multipleAnswers?: boolean;
  is_group?: boolean;
}

export interface MessageResponse<T = unknown> {
  status: boolean;
  message: string;
  data?: T;
}
