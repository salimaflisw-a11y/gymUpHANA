export interface MemberBadge {
  memberBadgeId: number;
  badgeId: number;
  code: string;
  name: string;
  description: string;
  icon: string;
  category: string;
  unlockedAt: string;
  perkReward: string | null;
  isPerkClaimed: boolean;
  claimedAt?: string;
}

export interface GamificationProfile {
  memberId: number;
  totalBadgesUnlocked: number;
  totalBadgesAvailable: number;
  newlyAwarded: string[];
  badges: MemberBadge[];
}

export interface CommunityChallenge {
  id: number;
  title: string;
  description: string;
  metric: string;
  targetGoal: number;
  currentProgress: number;
  progressPercentage: number;
  startDate: string;
  endDate: string;
  reward: string;
  isJoined: boolean;
  myContribution: number;
  participantCount: number;
}

export interface LeaderboardUser {
  rank: number;
  memberId: number;
  name: string;
  currentStreak: number;
  lastActive: string | null;
}

export interface LeaderboardResponse {
  streakLeaders: LeaderboardUser[];
}

export interface ClaimPerkResponse {
  success: boolean;
  message: string;
  perk: string;
  claimedAt: string;
}

