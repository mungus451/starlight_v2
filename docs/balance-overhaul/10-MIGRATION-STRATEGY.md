---
layout: default
title: Migration Strategy
---

# Migration Strategy: Transitioning Existing Players

## Challenge

If the game has existing players when balance changes go live, we must handle migration carefully to preserve relative progress and maintain trust.

---

## Migration Principles

1. **No wealth confiscation**: Never remove credits/resources from accounts
2. **Preserve relative positions**: Player rankings should stay roughly the same
3. **Transparent communication**: Announce changes clearly with 7-day notice
4. **Gradual rollout**: Implement changes in phases, not all at once
5. **Escape hatch**: Have rollback plan if changes are unpopular

---

## Three-Phase Rollout

### Phase 1: Announcement & Monitoring (Days -7 to -1)

**Actions**:
1. Post announcement in-game and on forums
2. Explain WHY changes are needed (oligarchy crisis)
3. Detail WHAT is changing (zero interest, vault protection, etc.)
4. Show HOW it benefits different play styles

**Example Announcement**:
```
📢 BALANCE OVERHAUL INCOMING 📢

Starting [DATE], StarlightDominion V2 will undergo a comprehensive balance overhaul to 
address exponential wealth gaps and improve new player experience.

KEY CHANGES:
✅ Bank interest → ZERO (prevents compounding oligarchy)
✅ Accounting Firm → Additive bonuses (prevents infinite scaling)
✅ Vault Protection → Graduated safety tiers
✅ Newbie Protection → 7-day full immunity, 30-day partial
✅ Unit Maintenance → Large armies cost upkeep
✅ Activity Bonuses → Rewards engagement without calendar hostages

WHY NOW:
Current systems create exponential wealth gaps where Day 1 early adopters become 
unbeatable oligarchs by Day 90. New players quit within 48 hours because they face 
insurmountable catch-up barriers.

YOUR WEALTH IS SAFE:
All existing credits, armies, and structures remain yours. Nothing is being taken away—
growth mechanics are being rebalanced going forward.

TIMELINE:
[DATE-7]: Announcement (today)
[DATE-3]: Q&A Session (Discord)
[DATE-1]: Final reminder
[DATE]: Changes go live
[DATE+7]: Balance review

Questions? Join us on Discord or forums.
```

**Monitoring**:
- Track player sentiment in forums/Discord
- Run baseline KPI snapshot (Gini, wealth distribution, power gaps)
- Identify top 10% players most affected by changes

---

### Phase 2: Staged Implementation (Day 0 - Day 14)

**Day 0: Core Economic Changes**
- ✅ Bank interest → 0
- ✅ Alliance treasury interest → 0
- ✅ Accounting firm → additive formula
- ❌ Don't enable: vault protection, maintenance, deployment costs yet

**Rationale**: Kill compounding first, add friction gradually.

---

**Day 3: Protection Systems**
- ✅ Vault protection tiers
- ✅ Newbie protection (full + partial)
- ✅ Anti-laundering rules

**Rationale**: Protect new players immediately, let veterans adjust.

---

**Day 7: Friction Systems**
- ✅ Unit maintenance
- ✅ Deployment costs
- ✅ Readiness decay

**Rationale**: Give players 1 week to experience zero interest before adding upkeep costs.

---

**Day 10: Engagement Systems**
- ✅ Weekly activity bonuses
- ✅ Multiplier budgets (clamping)

**Rationale**: Add positive incentives (activity) and caps (multipliers) last.

---

**Day 14: Full System Active**
- All changes live
- Monitor KPIs daily
- Respond to feedback

---

### Phase 3: Monitoring & Adjustment (Day 14 - Day 30)

**Daily Checks**:
- Wealth Gini coefficient
- Top 1% wealth share
- Power gap metrics
- Player engagement (attacks/day)
- Forum sentiment

**Weekly Reviews**:
- Are KPIs hitting targets?
- Are players adapting strategies?
- Is new player retention improving?
- Are veterans leaving?

**Adjustment Triggers**:
- If Gini > 0.70: Increase friction (maintenance, deployment costs)
- If attacks/day < 0.5: Reduce deployment costs or increase activity bonuses
- If veteran retention < 80%: Consider rollback or tweaks

---

## Handling Existing Player Concerns

### Concern 1: "I invested in accounting firm, now it's nerfed!"

**Response**: 
- Accounting firm still provides bonuses (1%/level additive vs 5%/level multiplicative)
- You're still ahead of players who didn't invest
- Old system created unsustainable wealth gaps
- New system ensures game survives long-term (benefits everyone)

---

### Concern 2: "My bank was earning 8% daily interest. Now it's zero!"

**Response**:
- Interest created exponential oligarchy where Day 1 = unbeatable by Day 90
- New players quit within 48 hours because gap is insurmountable
- Banking now provides insurance perks (rebuild discount, vault protection)
- Your wealth grows from structures, workers, and combat—not passive sitting

---

### Concern 3: "Maintenance costs are killing my massive army!"

**Response**:
- First 2k units are free (newbie buffer)
- Maintenance capped at 40% of gross income (won't bankrupt you)
- Old system allowed infinite hoarding with zero cost
- New system rewards active play over turtling
- If maintenance is high, invest in economy to raise income cap

---

### Concern 4: "I can't catch up to veterans now!"

**Response** (for new players):
- Vault protection gives you 7-30 days of safety
- Newbie protection prevents veteran predation
- Veterans now pay maintenance (you don't yet)
- Activity bonuses let engaged players catch up faster
- Multiplier caps prevent runaway veteran scaling

---

## Rollback Plan

### Conditions for Rollback
1. Veteran retention drops below 70% in first 14 days
2. KPIs worsen (Gini increases, attacks/day drops)
3. Overwhelming player backlash (>75% negative sentiment)

### Rollback Procedure
1. Revert config to original values (saved in git)
2. Disable new systems (vault protection, maintenance, etc.)
3. Announce rollback with explanation
4. Refund any deployment costs/maintenance paid
5. Re-analyze balance with player feedback
6. Propose adjusted changes (Lane B or Lane C from banking doc)

---

## Data to Track Pre/Post Migration

| Metric | Pre-Change | Day 7 | Day 14 | Day 30 | Target |
|--------|------------|-------|--------|--------|--------|
| **Gini Coefficient** | 0.68 | 0.66 | 0.63 | 0.60 | <0.65 ✅ |
| **Top 1% Share** | 42% | 39% | 36% | 32% | <35% ✅ |
| **Attacks/Day (Avg)** | 0.4 | 0.6 | 0.9 | 1.2 | ≥1.0 ✅ |
| **New Player Retention** | 18% | 32% | 45% | 52% | >50% ✅ |
| **Veteran Retention** | 95% | 92% | 88% | 85% | >80% ✅ |

---

## Communication Templates

### Day -7 Announcement
*(See Phase 1 example above)*

---

### Day -3 Q&A Session Topics
- "Will my investments be wasted?"
- "How do I adapt my strategy?"
- "Can I preview new systems?"
- "What if I disagree with changes?"

---

### Day 0 Launch Announcement
```
🚀 BALANCE OVERHAUL IS LIVE 🚀

As of today, the following changes are active:
✅ Bank interest = 0 (insurance model active)
✅ Accounting firm = additive bonuses
✅ Alliance treasury interest = 0

Coming soon (staged rollout):
📅 Day 3: Vault protection + newbie shields
📅 Day 7: Maintenance + deployment costs
📅 Day 10: Activity bonuses + multiplier caps

Track your stats at /balance-kpis (new page)

Feedback? Discord #balance-feedback
```

---

### Day 14 Review Post
```
📊 BALANCE OVERHAUL: 2-WEEK REPORT 📊

Key Metrics:
✅ Wealth Gini: 0.68 → 0.63 (improved)
✅ Attacks/day: 0.4 → 0.9 (more engagement)
✅ New player retention: 18% → 45% (major win)
⚠️ Veteran retention: 95% → 88% (slight dip, monitoring)

Player Feedback Highlights:
👍 "Finally feel like I can catch up"
👍 "Love the activity bonuses"
👎 "Maintenance too high for my 500k army"
👎 "Miss my interest income"

Adjustments:
- Maintenance soft cap raised from 35% → 40%
- Activity bonus points cap raised from 100 → 120

Next Review: Day 30

Thank you for adapting! Balance is iterative.
```

---

## Conclusion

Migration requires careful communication, staged rollout, and continuous monitoring. The goal is to improve balance without alienating existing players or losing trust.

**Key Success Factors**:
1. Transparent reasoning (WHY changes are needed)
2. Gradual implementation (not all at once)
3. Data-driven adjustments (KPI tracking)
4. Player feedback loop (Discord, forums)
5. Rollback escape hatch (if things go wrong)

---

**End of Balance Overhaul Documentation**

For questions or further discussion, contact the game balance architect.
