export interface GroupSummary {
    id: string;
    subject: string;
    owner?: string;
    creation?: number;
    size?: number;
    desc?: string;
}
export interface GroupParticipant {
    id: string;
    admin?: "admin" | "superadmin" | null;
}
export interface GroupMetadata extends GroupSummary {
    participants?: GroupParticipant[];
}
export interface ListGroupsParams {
    sessionId: string;
    includeParticipants?: boolean;
}
//# sourceMappingURL=group.d.ts.map